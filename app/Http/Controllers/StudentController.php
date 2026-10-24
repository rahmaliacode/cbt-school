<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Services\ExamEngine;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    private function activeAttempt(Request $r): bool
    {
        return $r->session()->has('participant_id') && $r->session()->has('exam_session_id') && Attempt::where('participant_id', $r->session()->get('participant_id'))->where('exam_session_id', $r->session()->get('exam_session_id'))->where('status', 'in_progress')->exists();
    }

    public function login(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        if ($this->activeAttempt($r)) return redirect('/exam');
        return view('student.login');
    }

    public function authenticate(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        if ($this->activeAttempt($r)) return redirect('/exam');
        $data = $r->validate(['participant_number' => 'required|string', 'token' => 'required|string']);
        $s = ExamSession::where('token', $data['token'])->first();
        $p = Participant::where('participant_number', $data['participant_number'])->first();
        $engine->require($s && $p && $s->participants()->where('participants.id', $p->id)->exists(), 'Nomor peserta atau token sesi tidak valid.');
        $engine->expire();
        $s->refresh();
        $engine->require($s->status !== 'ended', 'Sesi sudah berakhir.');
        $engine->require(! Attempt::where('participant_id', $p->id)->where('exam_session_id', $s->id)->where('status', '!=', 'in_progress')->exists(), 'Ujian sudah diselesaikan.');
        $r->session()->regenerate();
        $r->session()->put(['participant_id' => $p->id, 'exam_session_id' => $s->id]);

        return redirect('/waiting');
    }

    private function identity(Request $r): array
    {
        abort_unless($r->session()->has('participant_id') && $r->session()->has('exam_session_id'), 401);

        return [Participant::findOrFail($r->session()->get('participant_id')), ExamSession::findOrFail($r->session()->get('exam_session_id'))];
    }

    private function attempt(Request $r): Attempt
    {
        [$p,$s] = $this->identity($r);
        $attempt = Attempt::where('participant_id', $p->id)->where('exam_session_id', $s->id)->first();
        if (! $attempt) {
            // Attempt is gone after an official reset (or was never started): send the participant back to the waiting room.
            if (DB::table('activity_logs')->where('event', 'attempt_reset')->where('context->participant_id', $p->id)->where('context->exam_session_id', $s->id)->exists()) {
                $r->session()->flash('success', 'Ujian Anda direset oleh panitia. Silakan mulai kembali dari awal.');
            }
            throw new HttpResponseException($r->expectsJson() ? response()->json(['status' => 'reset', 'redirect' => '/waiting']) : redirect('/waiting'));
        }

        return $attempt;
    }

    public function waiting(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        if ($this->activeAttempt($r)) return redirect('/exam');
        [$participant,$session] = $this->identity($r);
        $attempt = Attempt::where('participant_id', $participant->id)->where('exam_session_id', $session->id)->first();

        return view('student.waiting', compact('participant', 'session', 'attempt'));
    }

    public function start(Request $r, ExamEngine $engine)
    {
        [$p,$s] = $this->identity($r);
        $engine->startAttempt($s, $p->id);

        return redirect('/exam');
    }

    public function exam(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        $attempt = $this->attempt($r);
        if ($attempt->status !== 'in_progress') {
            return redirect('/finished');
        }

        return view('student.exam', ['attempt' => $attempt]);
    }

    public function state(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        $a = $this->attempt($r);
        if ($a->status === 'in_progress') {
            $a->update(['last_seen_at' => now()]);
        }
        $s = $a->examSession;
        $questions = collect($s->question_snapshot)->keyBy('id');
        $safe = [];
        foreach ($a->question_order as $id) {
            $q = $questions[$id];
            $options = collect($q['options'])->keyBy('id');
            $safe[] = ['id' => $id, 'text' => $q['text'], 'image_url' => empty($q['image_path']) ? null : '/exam/media/'.$id, 'image_alt' => $q['image_alt'] ?? 'Gambar pendukung soal', 'options' => array_map(fn ($oid) => ['id' => $oid, 'text' => $options[$oid]['text']], $a->option_order[$id])];
        }

        return response()->json(['status' => $a->status, 'server_now' => now()->getTimestampMs(), 'deadline_at' => $a->deadline_at->getTimestampMs(), 'questions' => $safe, 'answers' => $a->answers()->get(['question_id', 'answer_option_id', 'is_flagged', 'revision'])]);
    }

    public function focusEvents(Request $r, ExamEngine $engine)
    {
        $data = $r->validate(['events' => 'required|array|min:1|max:20', 'events.*.id' => 'required|uuid', 'events.*.type' => 'required|in:exam_open,fullscreen_enter,fullscreen_exit,fullscreen_unavailable,supervised_mode,page_hidden,window_blur,navigation_blocked,shortcut_blocked']);
        $engine->expire();
        $identity = $this->attempt($r);
        return \Illuminate\Support\Facades\DB::transaction(function () use ($identity, $data, $engine) {
            $session = ExamSession::lockForUpdate()->findOrFail($identity->exam_session_id);
            $attempt = Attempt::lockForUpdate()->findOrFail($identity->id);
            if ($session->status !== 'running' || $attempt->status !== 'in_progress') {
                return response()->json(['status' => $attempt->status, 'accepted' => 0]);
            }
            $query = \Illuminate\Support\Facades\DB::table('activity_logs')->where('event', 'exam.focus')->where('context->attempt_id', $attempt->id);
            $count = $query->count(); $accepted = 0;
            foreach ($data['events'] as $event) {
                if ($count >= 500) break;
                if ((clone $query)->where('context->event_id', $event['id'])->exists()) continue;
                $engine->log('exam.focus', ['attempt_id' => $attempt->id, 'exam_session_id' => $session->id, 'participant_id' => $attempt->participant_id, 'event_id' => $event['id'], 'type' => $event['type']]);
                $count++; $accepted++;
            }
            return response()->json(['status' => $attempt->status, 'accepted' => $accepted]);
        });
    }

    public function image(Request $r, int $questionId, ExamEngine $engine)
    {
        $engine->expire();
        $a = $this->attempt($r);
        abort_unless($a->status === 'in_progress' && in_array($questionId, $a->question_order), 403);
        $q = collect($a->examSession->question_snapshot)->firstWhere('id', $questionId);
        return \App\Services\QuestionMedia::response($q['image_path'] ?? null);
    }

    public function save(Request $r, ExamEngine $engine)
    {
        $data = $r->validate(['question_id' => 'required|integer', 'answer_option_id' => 'present|nullable|integer', 'is_flagged' => 'required|boolean', 'revision' => 'required|integer|min:1|max:2147483647']);
        $data['question_id'] = (int) $data['question_id'];
        $data['answer_option_id'] = $data['answer_option_id'] === null ? null : (int) $data['answer_option_id'];
        $a = $engine->save($this->attempt($r), $data);

        return response()->json(['status' => $a->status, 'saved' => $a->status === 'in_progress']);
    }

    public function submit(Request $r, ExamEngine $engine)
    {
        $engine->submit($this->attempt($r));

        return $r->expectsJson() ? response()->json(['redirect' => '/finished']) : redirect('/finished');
    }

    public function finished(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        $attempt = $this->attempt($r);
        abort_if($attempt->status === 'in_progress', 403);

        return view('student.finished', compact('attempt'));
    }

    public function logout(Request $r, ExamEngine $engine)
    {
        $engine->expire();
        if ($this->activeAttempt($r)) {
            return redirect('/exam')->with('success', 'Kumpulkan jawaban sebelum keluar dari ujian.');
        }
        $r->session()->forget(['participant_id', 'exam_session_id']);
        $r->session()->regenerateToken();

        return redirect('/');
    }
}
