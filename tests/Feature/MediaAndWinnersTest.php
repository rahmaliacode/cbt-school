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
}
