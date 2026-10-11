<?php
namespace Tests\Feature;

use App\Models\{Attempt, ExamSession, Participant, Question, Subject, User};
use App\Services\{ExamEngine, WinnerSummary};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndWinnersTest extends TestCase
{
    use RefreshDatabase;

    private function setupSession(): ExamSession
    {
        $subject = Subject::create(['name' => 'Matematika']);
        $q = Question::create(['subject_id' => $subject->id, 'number' => 1, 'text' => 'Soal gambar']);
        foreach (range('A', 'D') as $label) $q->options()->create(['label' => $label, 'text' => $label, 'is_correct' => $label === 'A']);
        $s = ExamSession::create(['subject_id' => $subject->id, 'name' => 'Final', 'token' => 'TOKEN123', 'question_count' => 1, 'duration_minutes' => 15, 'correct_score' => 4, 'incorrect_score' => -1, 'blank_score' => 0, 'option_count' => 4, 'tie_breaker' => 'none']);
        foreach (range(1, 2) as $i) $s->participants()->attach(Participant::create(['participant_number' => 'P'.$i, 'name' => 'Peserta '.$i])->id);
        return $s;
    }

    public function test_image_upload_is_private_and_snapshotted(): void
    {
        Storage::fake('local');
        $s = $this->setupSession(); $q = Question::first();
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'a@example.test', 'password' => 'secret', 'is_admin' => true]);
        $payload = ['subject_id' => $q->subject_id, 'number' => 1, 'text' => 'Soal gambar', 'key' => 'A', 'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'], 'image_alt' => 'Diagram segitiga'];
        $this->actingAs($admin)->put('/admin/questions/'.$q->id, $payload + ['image' => UploadedFile::fake()->image('diagram.png')])->assertSessionHasNoErrors();
        $path = $q->refresh()->image_path; Storage::disk('local')->assertExists($path);
        $this->put('/admin/questions/'.$q->id, $payload + ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
        $engine = app(ExamEngine::class); $engine->startSession($s); $s->refresh();
        $this->assertSame($path, $s->question_snapshot[0]['image_path']);
        $this->get('/exam/media/'.$q->id)->assertUnauthorized();
        $p = $s->participants->first();
        $this->withSession(['participant_id' => $p->id, 'exam_session_id' => $s->id])->get('/exam/media/'.$q->id)->assertNotFound();
        $a = $engine->startAttempt($s, $p->id);
        $this->get('/exam/state')->assertJsonPath('questions.0.image_url', '/exam/media/'.$q->id)->assertJsonMissing(['image_path' => $path]);
        $this->get('/exam/media/'.$q->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/exam/media/999999')->assertForbidden();
        $this->put('/admin/questions/'.$q->id, $payload)->assertSessionHasErrors('question');
        $engine->submit($a);
        $this->get('/exam/media/'.$q->id)->assertForbidden();
    }

    public function test_winners_include_all_ties_and_exclude_unlocked_sessions(): void
    {
        $s = $this->setupSession(); $engine = app(ExamEngine::class); $engine->startSession($s); $s->refresh();
        foreach ($s->participants as $p) $engine->submit($engine->startAttempt($s, $p->id));
        $engine->endSession($s); $s->refresh();
        $summary = app(WinnerSummary::class);
        $this->assertNull($summary->get()->first()['session']);
        $s->update(['results_locked_at' => now()]);
        $card = $summary->get()->first();
        $this->assertCount(2, $card['winners']);
        $later = ExamSession::create(['subject_id' => $s->subject_id, 'name' => 'Sesi lain', 'token' => 'TOKEN456', 'question_count' => 1, 'duration_minutes' => 15, 'status' => 'ended', 'results_locked_at' => now()->addMinute()]);
        $this->assertSame($later->id, $summary->get()->first()['session']->id);
        $this->assertSame($s->id, $summary->get([$s->subject_id => $s->id])->first()['session']->id);
        $s->update(['results_locked_at' => null]);
        $this->assertSame($later->id, $summary->get([$s->subject_id => $s->id])->first()['session']->id);
    }
    public function test_focus_audit_is_scoped_idempotent_and_does_not_change_results(): void
    {
        $s = $this->setupSession(); $engine = app(ExamEngine::class); $engine->startSession($s); $s->refresh();
        $p = $s->participants->first(); $a = $engine->startAttempt($s, $p->id);
        $payload = ['events' => [['id' => 'c4d6e28b-6492-4b59-88b6-2598e58df6f9', 'type' => 'page_hidden']]];
        $this->postJson('/exam/focus-events', $payload)->assertUnauthorized();
        $this->withSession(['participant_id' => $p->id, 'exam_session_id' => $s->id])->postJson('/exam/focus-events', $payload)->assertOk()->assertJsonPath('accepted', 1);
        $this->postJson('/exam/focus-events', $payload)->assertJsonPath('accepted', 0);
        $event = json_decode(\Illuminate\Support\Facades\DB::table('activity_logs')->where('event', 'exam.focus')->first()->context, true);
        $this->assertSame($a->id, $event['attempt_id']); $this->assertSame($p->id, $event['participant_id']);
        $this->postJson('/exam/focus-events', ['events' => [['id' => $payload['events'][0]['id'], 'type' => 'tampered']]])->assertUnprocessable();
        $this->post('/logout')->assertRedirect('/exam');
        $this->get('/')->assertRedirect('/exam');
        $this->get('/waiting')->assertRedirect('/exam');
        $this->post('/login', ['participant_number' => 'P2', 'token' => $s->token])->assertRedirect('/exam');
        $this->assertSame($p->id, session('participant_id'));
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'focus@example.test', 'password' => 'secret', 'is_admin' => true]);
        $this->actingAs($admin)->getJson('/admin/sessions/'.$s->id.'/monitor-data')->assertOk()->assertJsonPath('participants.0.focus_signals', 1);
        $other = $s->participants->last();
        $this->withSession(['participant_id' => $other->id])->postJson('/exam/focus-events', $payload + ['attempt_id' => $a->id])->assertNotFound();
        $this->withSession(['participant_id' => $p->id]);
        $engine->submit($a); $score = $a->refresh()->result->total_score;
        $this->postJson('/exam/focus-events', ['events' => [['id' => 'fe7273ea-47dd-486f-a529-74cf2ee9a856', 'type' => 'window_blur']]])->assertOk()->assertJsonPath('accepted', 0);
        $this->assertSame($score, $a->refresh()->result->total_score);
        $this->assertDatabaseCount('results', 1);
    }

}
