<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamEngine;
use App\Services\ParticipantImporter;
use App\Services\QuestionImporter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CbtTest extends TestCase
{
    use RefreshDatabase;

    private ExamEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ExamEngine::class);
    }

    private function makeSession(array $config = []): ExamSession
    {
        $subject = Subject::create(['name' => 'Matematika']);
        foreach (range(1, 3) as $i) {
            $q = Question::create(['subject_id' => $subject->id, 'number' => $i, 'text' => 'Soal '.$i]);
            foreach (['A', 'B', 'C', 'D'] as $label) {
                $q->options()->create(['label' => $label, 'text' => 'Opsi '.$label, 'is_correct' => $label === 'B']);
            }
        }
        $s = ExamSession::create(array_merge(['subject_id' => $subject->id, 'name' => 'Penyisihan', 'token' => 'TOKEN123', 'question_count' => 3, 'duration_minutes' => 90, 'correct_score' => 4, 'incorrect_score' => -1, 'blank_score' => 0, 'option_count' => 4, 'tie_breaker' => 'none'], $config));
        $p = Participant::create(['participant_number' => 'P-001', 'name' => 'Nadira']);
        $s->participants()->attach($p);
        $this->engine->startSession($s);

        return $s->refresh();
    }

    private function attempt(ExamSession $s): Attempt
    {
        return $this->engine->startAttempt($s, $s->participants->first()->id);
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('secret-password'), 'is_admin' => true]);
    }

    private function answer(Attempt $a, int $qid, bool $correct = true, int $revision = 1)
    {
        $q = collect($a->examSession->question_snapshot)->firstWhere('id', $qid);
        $o = collect($q['options'])->firstWhere('is_correct', $correct);

        return $this->engine->save($a, ['question_id' => $qid, 'answer_option_id' => $o['id'], 'is_flagged' => false, 'revision' => $revision]);
    }

    public function test_one_attempt_per_session_and_random_order_survives_reload(): void
    {
        $s = $this->makeSession(['shuffle_questions' => true, 'shuffle_options' => true]);
        $a = $this->attempt($s);
        $b = $this->attempt($s);
        $this->assertSame($a->id, $b->id);
        $this->assertSame($a->question_order, $b->question_order);
        $this->assertSame($a->option_order, $b->option_order);
        $this->assertDatabaseCount('attempts', 1);
        $this->expectException(QueryException::class);
        Attempt::create(collect($a->getAttributes())->except('id')->all());
    }

    public function test_score_correct_wrong_blank_and_double_submit_is_idempotent(): void
    {
        $a = $this->attempt($this->makeSession());
        $this->answer($a, $a->question_order[0]);
        $this->answer($a, $a->question_order[1], false);
        $first = $this->engine->submit($a);
        $second = $this->engine->submit($a);
        $this->assertSame($first->submitted_at->toIso8601String(), $second->submitted_at->toIso8601String());
        $this->assertDatabaseCount('results', 1);
        $r = $first->result;
        $this->assertEquals(3, $r->total_score);
        $this->assertSame(1, $r->correct_count);
        $this->assertSame(1, $r->incorrect_count);
        $this->assertSame(1, $r->blank_count);
        $this->assertDatabaseCount('participant_answers', 2);
    }

    public function test_snapshot_is_used_even_if_bank_and_configuration_are_changed(): void
    {
        $s = $this->makeSession(['correct_score' => 2.5, 'incorrect_score' => -0.5, 'blank_score' => 0.25]);
        $a = $this->attempt($s);
        $qid = $a->question_order[0];
        $this->answer($a, $qid);
        DB::table('answer_options')->where('question_id', $qid)->update(['is_correct' => false]);
        $s->update(['correct_score' => 100]);
        $r = $this->engine->submit($a)->result;
        $this->assertEquals(3, $r->total_score);
        $this->assertSame(1, $r->correct_count);
    }

    public function test_deadline_blocks_answers_and_finalizes_without_browser(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $this->answer($a, $a->question_order[0]);
        $this->travel(91)->minutes();
        $result = $this->answer($a, $a->question_order[1]);
        $this->assertSame('timed_out', $result->status);
        $this->assertDatabaseCount('participant_answers', 1);
        $this->assertSame(2, $result->result->blank_count);
        $this->engine->expire();
        $this->assertSame('ended', $s->fresh()->status);
        $this->assertTrue($result->submitted_at->equalTo($a->deadline_at));
    }

    public function test_expire_command_submits_inactive_participants(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $this->travel(91)->minutes();
        $this->artisan('cbt:expire')->assertSuccessful();
        $this->assertSame('timed_out', $a->fresh()->status);
        $this->assertDatabaseHas('results', ['attempt_id' => $a->id, 'blank_count' => 3]);
    }

    public function test_wrong_question_option_is_rejected(): void
    {
        $a = $this->attempt($this->makeSession());
        $foreign = collect($a->examSession->question_snapshot[1]['options'])->first()['id'];
        $this->expectException(ValidationException::class);
        $this->engine->save($a, ['question_id' => $a->question_order[0], 'answer_option_id' => $foreign, 'is_flagged' => false, 'revision' => 1]);
    }

    public function test_old_revision_cannot_overwrite_new_answer(): void
    {
        $a = $this->attempt($this->makeSession());
        $qid = $a->question_order[0];
        $this->answer($a, $qid, true, 2);
        try {
            $this->answer($a, $qid, false, 1);
            $this->fail('Expected stale revision rejection');
        } catch (ValidationException $e) {
        }
        $r = $this->engine->submit($a)->result;
        $this->assertSame(1, $r->correct_count);
    }

    public function test_submitted_attempt_cannot_be_reopened_or_edited(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $this->engine->submit($a);
        $saved = $this->answer($a, $a->question_order[0]);
        $this->assertSame('submitted', $saved->status);
        $this->assertDatabaseCount('participant_answers', 0);
        $this->expectException(ValidationException::class);
        $this->attempt($s);
    }

    public function test_early_end_finalizes_all_active_attempts(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $this->travel(5)->minutes();
        $this->engine->endSession($s);
        $this->assertSame('timed_out', $a->fresh()->status);
        $this->assertTrue($a->fresh()->submitted_at->equalTo($s->fresh()->ends_at));
    }

    public function test_start_rejects_mismatched_option_count(): void
    {
        $s = $this->makeSession();
        $s->update(['status' => 'draft', 'option_count' => 5]);
        $this->expectException(ValidationException::class);
        $this->engine->startSession($s);
    }

    public function test_unregistered_participant_cannot_start(): void
    {
        $s = $this->makeSession();
        $p = Participant::create(['participant_number' => 'OTHER', 'name' => 'Other']);
        $this->expectException(ValidationException::class);
        $this->engine->startAttempt($s, $p->id);
    }

    public function test_student_state_contains_no_key_or_score_and_saves_on_server(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $identity = ['participant_id' => $a->participant_id, 'exam_session_id' => $s->id];
        $state = $this->withSession($identity)->getJson('/exam/state')->assertOk()->json();
        $this->assertCount(3, $state['questions']);
        $this->assertArrayNotHasKey('is_correct', $state['questions'][0]['options'][0]);
        $this->assertArrayNotHasKey('total_score', $state);
        $q = $state['questions'][0];
        $this->postJson('/exam/answer', ['question_id' => $q['id'], 'answer_option_id' => $q['options'][0]['id'], 'is_flagged' => true, 'revision' => 1])->assertOk()->assertJson(['saved' => true]);
        $this->assertDatabaseHas('participant_answers', ['attempt_id' => $a->id, 'question_id' => $q['id'], 'is_flagged' => true]);
        $this->getJson('/exam/state')->assertJsonPath('answers.0.is_flagged', true);
    }

    public function test_student_cannot_access_admin_and_non_admin_user_forbidden(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $user = User::forceCreate(['name' => 'Other', 'email' => 'other@test.com', 'password' => Hash::make('secret'), 'is_admin' => false]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_monitor_never_returns_answers_or_score_even_with_live_score_on(): void
    {
        $s = $this->makeSession(['show_live_score' => true]);
        $a = $this->attempt($s);
        $this->answer($a, $a->question_order[0]);
        $this->actingAs($this->admin());
        $row = $this->getJson('/admin/sessions/'.$s->id.'/monitor-data')->assertOk()->json('participants.0');
        $this->assertSame('1/3', $row['progress']);
        foreach (['answers', 'total_score', 'correct_count', 'incorrect_count', 'is_correct', 'answer_option_id', 'question_snapshot'] as $key) {
            $this->assertArrayNotHasKey($key, $row);
        }
        $this->get('/admin/attempts/'.$a->id)->assertForbidden();
    }

    public function test_projector_display_requires_admin_and_hides_token(): void
    {
        $s = $this->makeSession();
        $this->get('/admin/sessions/'.$s->id.'/display')->assertRedirect('/admin/login');
        $this->actingAs($this->admin());
        $this->get('/admin/sessions/'.$s->id.'/display')->assertOk()->assertSee('Layar Pantau')->assertDontSee($s->token)->assertDontSee('Reset');
        $this->getJson('/admin/sessions/'.$s->id.'/monitor-data')->assertOk()->assertJsonPath('ends_at', $s->fresh()->ends_at->toIso8601String());
    }

    public function test_admin_password_can_be_reset_from_console(): void
    {
        $admin = $this->admin();
        $this->artisan('cbt:reset-admin-password', ['email' => 'missing@example.test'])->assertExitCode(1);
        $this->artisan('cbt:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('Password baru (minimal 12 karakter)', 'new-password-123')
            ->expectsQuestion('Ulangi password baru', 'typo-password-123')
            ->assertExitCode(1);
        $this->artisan('cbt:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('Password baru (minimal 12 karakter)', 'new-password-123')
            ->expectsQuestion('Ulangi password baru', 'new-password-123')
            ->assertExitCode(0);
        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'new-password-123'])->assertRedirect('/admin');
    }

    public function test_admin_cannot_change_configuration_or_used_questions(): void
    {
        $s = $this->makeSession();
        $this->actingAs($this->admin());
        $data = $s->only(['name', 'subject_id', 'token', 'question_count', 'duration_minutes', 'correct_score', 'incorrect_score', 'blank_score', 'option_count', 'tie_breaker']);
        $data['participant_ids'] = $s->participants->pluck('id')->all();
        $data['correct_score'] = 20;
        $this->putJson('/admin/sessions/'.$s->id, $data)->assertUnprocessable();
        $this->assertEquals(4, $s->fresh()->correct_score);
        $q = Question::first();
        $this->putJson('/admin/questions/'.$q->id, ['subject_id' => $s->subject_id, 'number' => $q->number, 'text' => 'Changed', 'options' => ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'], 'key' => 'A'])->assertUnprocessable();
        $this->delete('/admin/questions/'.$q->id)->assertStatus(422);
    }

    public function test_import_validates_all_rows_and_rolls_back_duplicate_commit(): void
    {
        Subject::create(['name' => 'IPA']);
        $importer = app(QuestionImporter::class);
        $preview = $importer->validateRows([[1, 'IPA', 'Soal', 'A', 'B', 'C', 'D', '', 'A'], [2, 'IPA', 'Soal', 'A', '', 'C', 'D', '', 'E']]);
        $this->assertCount(1, $preview['rows']);
        $this->assertCount(1, $preview['errors']);
        $this->assertSame(3, $preview['errors'][0]['row']);
        $this->assertDatabaseCount('questions', 0);
        $good = $importer->validateRows([[1, 'IPA', 'Soal', 'A', 'B', 'C', 'D', '', 'B'], [2, 'IPA', 'Soal 2', 'A', 'B', 'C', 'D', 'E', 'E']]);
        $importer->commit($good['rows']);
        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('answer_options', 9);
        $rows = [array_merge($good['rows'][0], ['number' => 3]), $good['rows'][0]];
        try {
            $importer->commit($rows);
            $this->fail('Expected duplicate rejection');
        } catch (ValidationException $e) {
        }
        $this->assertDatabaseCount('questions', 2);
    }

    public function test_invalid_import_cannot_be_committed_and_preview_requires_confirmation(): void
    {
        Subject::create(['name' => 'IPA']);
        $this->actingAs($this->admin());
        $this->post('/admin/questions/preview', ['paste' => "1\tIPA\tSoal\tA\tB\tC\tD\t\tB"])->assertOk()->assertSee('Simpan 1 soal');
        $this->assertDatabaseCount('questions', 0);
        $this->post('/admin/questions/preview', ['paste' => "2\tIPA\tSoal\tA\t\tC\tD\t\tE"])->assertOk()->assertSee('Tidak ada data yang dimasukkan');
        $this->postJson('/admin/questions/import')->assertUnprocessable();
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_pasted_multi_line_excel_cell_stays_in_one_question(): void
    {
        Subject::create(['name' => 'Bahasa Indonesia']);
        $this->actingAs($this->admin());
        $paste = "1\tBahasa Indonesia\t\"Paragraf pertama \"\"kutipan\"\".\r\n\r\nParagraf kedua.\"\tA\tB\tC\tD\t\tA\r\n2\tBahasa Indonesia\tSoal dua\tA\tB\tC\tD\t\tB";
        $this->post('/admin/questions/preview', ['paste' => $paste])->assertOk()->assertSee('Simpan 2 soal');
        $this->post('/admin/questions/import')->assertRedirect();
        $this->assertSame("Paragraf pertama \"kutipan\".\n\nParagraf kedua.", Question::where('number', 1)->value('text'));
    }

    public function test_ties_and_configured_tie_breakers_match_csv(): void
    {
        $s = $this->makeSession(['correct_score' => 0, 'incorrect_score' => 0]);
        $a = $this->attempt($s);
        $this->answer($a, $a->question_order[0]);
        $this->engine->submit($a);
        $p = Participant::create(['participant_number' => 'P-002', 'name' => '=Formula']);
        $s->participants()->attach($p);
        $b = $this->engine->startAttempt($s, $p->id);
        $this->travel(1)->seconds();
        $this->engine->submit($b);
        $none = $this->engine->leaderboard($s);
        $this->assertSame([1, 1], $none->pluck('rank')->all());
        $c = $s->configuration_snapshot;
        $c['tie_breaker'] = 'correct_count';
        $s->update(['configuration_snapshot' => $c]);
        $this->assertSame([1, 2], $this->engine->leaderboard($s)->pluck('rank')->all());
        $c['tie_breaker'] = 'earliest_submit';
        $s->update(['configuration_snapshot' => $c]);
        $ranked = $this->engine->leaderboard($s);
        $this->assertSame($a->id, $ranked[0]->attempt_id);
        $this->actingAs($this->admin());
        $this->engine->endSession($s);
        $response = $this->get('/admin/sessions/'.$s->id.'/export')->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=Formula", $csv);
        $this->assertStringContainsString('rank,participant_number,name', $csv);
        $this->get('/admin/sessions/'.$s->id.'/results')->assertOk()->assertSee('Nadira');
    }

    public function test_reset_is_audited_and_allows_only_one_replacement_attempt(): void
    {
        $s = $this->makeSession();
        $a = $this->attempt($s);
        $this->answer($a, $a->question_order[0]);
        $this->engine->submit($a);
        $this->actingAs($this->admin())->postJson('/admin/attempts/'.$a->id.'/reset', ['reason' => 'Kendala perangkat'])->assertOk()->assertJson(['reset' => true]);
        $this->assertDatabaseCount('attempts', 0);
        $this->assertDatabaseCount('participant_answers', 0);
        $this->assertDatabaseCount('results', 0);
        $this->assertDatabaseHas('activity_logs', ['event' => 'attempt_reset']);
        $this->withSession(['participant_id' => $a->participant_id, 'exam_session_id' => $s->id]);
        $this->getJson('/exam/state')->assertOk()->assertExactJson(['status' => 'reset', 'redirect' => '/waiting']);
        $this->get('/waiting')->assertOk()->assertSee('Ujian Anda direset oleh panitia');
        $this->get('/exam')->assertRedirect('/waiting');
        $new = $this->attempt($s);
        $this->assertNotSame($a->id, $new->id);
        $this->assertTrue($new->deadline_at->equalTo($s->ends_at));
    }

    public function test_all_main_pages_and_template_render(): void
    {
        $s = $this->makeSession();
        $this->withSession(['participant_id' => $s->participants->first()->id, 'exam_session_id' => $s->id]);
        $this->get('/')->assertOk();
        $this->get('/waiting')->assertOk();
        $a = $this->attempt($s);
        $this->get('/')->assertRedirect('/exam');
        $this->get('/waiting')->assertRedirect('/exam');
        $this->get('/exam')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/subjects', '/admin/participants', '/admin/questions', '/admin/sessions', '/admin/sessions/create', '/admin/sessions/'.$s->id.'/edit', '/admin/sessions/'.$s->id.'/monitor'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/questions/template')->assertOk();
        $this->engine->submit($a);
        $this->get('/finished')->assertOk()->assertSee('Skor total');
        $this->get('/admin/attempts/'.$a->id)->assertForbidden();
        $this->engine->endSession($s);
        $this->get('/admin/attempts/'.$a->id)->assertOk();
    }

    public function test_real_excel_template_upload_preview_and_atomic_commit(): void
    {
        Subject::create(['name' => 'Matematika']);
        $this->actingAs($this->admin());
        $bytes = $this->get('/admin/questions/template')->assertOk()->streamedContent();
        $file = UploadedFile::fake()->createWithContent('questions.xlsx', $bytes);
        $this->post('/admin/questions/preview', ['file' => $file])->assertOk()->assertSee('Simpan 1 soal');
        $this->assertDatabaseCount('questions', 0);
        $this->post('/admin/questions/import')->assertRedirect('/admin/questions');
        $this->assertDatabaseCount('questions', 1);
        $this->assertDatabaseCount('answer_options', 4);
        $this->assertDatabaseHas('answer_options', ['label' => 'B', 'text' => '4', 'is_correct' => true]);
        $this->postJson('/admin/questions/import')->assertUnprocessable();
    }

    public function test_admin_session_create_and_full_student_login_to_finished_flow(): void
    {
        $subject = Subject::create(['name' => 'IPA']);
        $participant = Participant::create(['participant_number' => 'P-001', 'name' => 'Raka']);
        app(QuestionImporter::class)->commit(app(QuestionImporter::class)->validateRows([[1, 'IPA', 'Berapa?', '0', '1', '2', '3', '', 'A']])['rows']);
        $this->actingAs($this->admin());
        $this->post('/admin/sessions', ['subject_id' => $subject->id, 'name' => 'Final', 'token' => 'FINAL123', 'question_count' => 1, 'duration_minutes' => 10, 'correct_score' => 4, 'incorrect_score' => -1, 'blank_score' => 0, 'option_count' => 4, 'tie_breaker' => 'none', 'participant_ids' => [$participant->id], 'show_live_score' => 1])->assertRedirect('/admin/sessions');
        $session = ExamSession::first();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->post('/login', ['participant_number' => 'P-001', 'token' => 'WRONG'])->assertSessionHasErrors();
        $this->post('/login', ['participant_number' => 'P-001', 'token' => 'FINAL123'])->assertRedirect('/waiting');
        $this->postJson('/start')->assertUnprocessable();
        $this->actingAs(User::where('is_admin', true)->first())->post('/admin/sessions/'.$session->id.'/start')->assertRedirect();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->post('/login', ['participant_number' => 'P-001', 'token' => 'FINAL123'])->assertRedirect('/waiting');
        $this->travel(2)->minutes();
        $this->post('/start')->assertRedirect('/exam');
        $attempt = Attempt::first();
        $this->assertTrue($attempt->deadline_at->equalTo($session->fresh()->ends_at));
        $q = $this->getJson('/exam/state')->assertOk()->json('questions.0');
        $this->postJson('/exam/answer', ['question_id' => $q['id'], 'answer_option_id' => $q['options'][0]['id'], 'is_flagged' => false, 'revision' => 1])->assertOk();
        $this->postJson('/exam/submit')->assertOk()->assertJson(['redirect' => '/finished']);
        $this->postJson('/exam/submit')->assertOk();
        $this->assertDatabaseCount('results', 1);
        $this->get('/finished')->assertOk()->assertSee('Skor total')->assertSee('4.00');
        $this->actingAs(User::where('is_admin', true)->first());
        $this->post('/admin/sessions/'.$session->id.'/end')->assertRedirect();
        $this->post('/admin/sessions/'.$session->id.'/lock-results')->assertRedirect();
        $this->get('/finished')->assertOk()->assertSee('Skor total')->assertSee('4.00');
        $this->post('/logout');
        $this->post('/login', ['participant_number' => 'P-001', 'token' => 'FINAL123'])->assertSessionHasErrors();
    }

    public function test_import_accepts_numeric_zero_and_reports_duplicate_normalized_number(): void
    {
        Subject::create(['name' => 'IPA']);
        $preview = app(QuestionImporter::class)->validateRows([['01', 'IPA', '0', '0', '1', '2', '3', '0', 'E'], [1, 'IPA', 'Soal', '0', '1', '2', '3', '', 'A']]);
        $this->assertCount(1, $preview['rows']);
        $this->assertCount(5, $preview['rows'][0]['options']);
        $this->assertCount(1, $preview['errors']);
        $this->assertStringContainsString('duplikat', $preview['errors'][0]['message']);
    }

    public function test_participant_import_preview_commit_and_duplicate_rejection(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/participants/template')->assertOk();
        $this->post('/admin/participants/preview', ['paste' => "Nomor Peserta\tNama\tAsal Sekolah\nP-009\tSiswa Contoh\tSMP Contoh"])
            ->assertOk()->assertSee('Siswa Contoh')->assertSee('Simpan 1 peserta');
        $this->assertDatabaseCount('participants', 0);
        $this->post('/admin/participants/import')->assertRedirect('/admin/participants');
        $this->assertDatabaseHas('participants', ['participant_number' => 'P-009', 'name' => 'Siswa Contoh']);
        $this->postJson('/admin/participants/import')->assertUnprocessable();
        $this->post('/admin/participants/preview', ['paste' => "P-010\tValid\tSMP\nP-009\tDuplicate\tSMP"])
            ->assertOk()->assertSee('Nomor peserta duplikat')->assertDontSee('Simpan 1 peserta');
        $this->postJson('/admin/participants/import')->assertUnprocessable();
        $this->assertDatabaseCount('participants', 1);
    }

    public function test_participant_import_reads_excel_and_revalidates_before_atomic_commit(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get('/admin/participants/template')->assertOk();
        $upload = UploadedFile::fake()->createWithContent('participants.xlsx', $response->streamedContent());
        $this->post('/admin/participants/preview', ['file' => $upload])->assertOk()->assertSee('Peserta Contoh');
        Participant::create(['participant_number' => 'P-004', 'name' => 'Added concurrently']);
        $this->postJson('/admin/participants/import')->assertUnprocessable();
        $this->assertDatabaseCount('participants', 1);
        $preview = app(ParticipantImporter::class)->validateRows([['p-004', 'Duplicate', ''], ['P-005', '', '']]);
        $this->assertCount(2, $preview['errors']);
    }

    public function test_score_publication_lock_and_unlock_require_correct_state_and_audit(): void
    {
        $s = $this->makeSession(['show_live_score' => false]);
        $a = $this->attempt($s);
        $this->engine->submit($a);
        $this->actingAs($this->admin());
        $this->get('/admin/sessions/'.$s->id.'/results')->assertOk()->assertDontSee('Nadira');
        $this->get('/admin/sessions/'.$s->id.'/export')->assertForbidden();
        $this->postJson('/admin/sessions/'.$s->id.'/lock-results')->assertUnprocessable();
        $this->engine->endSession($s);
        $this->get('/admin/sessions/'.$s->id.'/results')->assertOk()->assertSee('Nadira')->assertSee('DRAFT');
        $this->post('/admin/sessions/'.$s->id.'/lock-results')->assertRedirect();
        $this->assertNotNull($s->fresh()->results_locked_at);
        $this->get('/admin/sessions/'.$s->id.'/results')->assertOk()->assertSee('FINAL');
        $excel = $this->get('/admin/sessions/'.$s->id.'/export-excel')->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'cbt-export');
        file_put_contents($path, $excel);
        $book = IOFactory::load($path);
        unlink($path);
        $this->assertSame(['Ranking', 'Configuration', 'Audit'], $book->getSheetNames());
        $this->assertSame('Nadira', $book->getSheetByName('Ranking')->getCell('C2')->getValue());
        $this->assertSame('FINAL', $book->getSheetByName('Configuration')->getCell('B3')->getValue());
        $this->postJson('/admin/sessions/'.$s->id.'/unlock-results', ['reason' => 'x'])->assertUnprocessable();
        $this->post('/admin/sessions/'.$s->id.'/unlock-results', ['reason' => 'Verifikasi ulang nilai'])->assertRedirect();
        $this->assertNull($s->fresh()->results_locked_at);
        $this->assertDatabaseHas('activity_logs', ['event' => 'results_unlocked']);
    }

    public function test_duration_tie_break_uses_elapsed_time_instead_of_absolute_submit_time(): void
    {
        $s = $this->makeSession(['tie_breaker' => 'earliest_submit']);
        $a = $this->attempt($s);
        $this->travel(10)->seconds();
        $p = Participant::create(['participant_number' => 'P-002', 'name' => 'Later entrant']);
        $s->participants()->attach($p);
        $b = $this->engine->startAttempt($s, $p->id);
        $this->travel(1)->seconds();
        $this->engine->submit($a);
        $this->travel(1)->seconds();
        $this->engine->submit($b);
        $this->assertSame($b->id, $this->engine->leaderboard($s)[0]->attempt_id);
    }
    public function test_dashboard_explains_missing_preparation_and_session_form_has_score_preview(): void
    {
        $subject = Subject::create(['name' => 'IPA']);
        ExamSession::create(['subject_id' => $subject->id, 'name' => 'Draft preparation', 'token' => 'DRAFT123', 'question_count' => 30, 'duration_minutes' => 90, 'correct_score' => 4, 'incorrect_score' => -1, 'blank_score' => 0, 'option_count' => 4, 'tie_breaker' => 'none']);
        $this->actingAs($this->admin());
        $this->get('/admin')->assertOk()->assertSee('0 peserta ditugaskan')->assertSee('0 soal tersedia')->assertSee('30 diperlukan')->assertSee('Lengkapi persiapan dahulu')->assertDontSee('Mulai sesi →');
        $this->get('/admin/sessions/create')->assertOk()->assertSee('data-score-simulator', false)->assertSee('PREVIEW PENILAIAN')->assertSee('Jumlah benar contoh');
    }

}
