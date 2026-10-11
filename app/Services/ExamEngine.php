<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\ExamSession;
use App\Models\ParticipantAnswer;
use App\Models\Question;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamEngine
{
    public function log(string $event, array $context): void
    {
        DB::table('activity_logs')->insert(['user_id' => auth()->id(), 'event' => $event, 'context' => json_encode($context), 'created_at' => now()]);
    }

    public function startSession(ExamSession $session): void
    {
        DB::transaction(function () use ($session) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            $this->require($s->status === 'draft', 'Sesi sudah dimulai.');
            $questions = Question::with('options')->where('subject_id', $s->subject_id)->orderBy('number')->get();
            $this->require($questions->count() >= $s->question_count, 'Jumlah soal belum mencukupi.');
            $questions = $questions->take($s->question_count);
            foreach ($questions as $q) {
                $this->require($q->options->count() === $s->option_count && $q->options->where('is_correct', true)->count() === 1, 'Jumlah opsi atau kunci soal tidak sesuai konfigurasi sesi.');
            } $s->update(['status' => 'running', 'started_at' => now(), 'ends_at' => now()->addMinutes($s->duration_minutes), 'configuration_snapshot' => $s->only(['question_count', 'duration_minutes', 'correct_score', 'incorrect_score', 'blank_score', 'option_count', 'tie_breaker', 'shuffle_questions', 'shuffle_options', 'show_live_score']), 'question_snapshot' => $questions->map(fn ($q) => ['id' => $q->id, 'text' => $q->text, 'image_path' => $q->image_path, 'image_alt' => $q->image_alt, 'options' => $q->options->map(fn ($o) => $o->only(['id', 'text', 'is_correct']))->all()])->all()]);
            $this->log('session_started', ['exam_session_id' => $s->id]);
        });
    }

    public function startAttempt(ExamSession $session, int $participantId): Attempt
    {
        return DB::transaction(function () use ($session, $participantId) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            $this->require($s->status === 'running' && now()->lt($s->ends_at), 'Sesi belum dimulai atau sudah berakhir.');
            $this->require($s->participants()->where('participants.id', $participantId)->exists(), 'Peserta tidak terdaftar di sesi ini.');
            $existing = Attempt::where('exam_session_id', $s->id)->where('participant_id', $participantId)->first();
            if ($existing) {
                $this->require($existing->status === 'in_progress', 'Ujian sudah dikumpulkan.');

                return $existing;
            }
            $order = array_column($s->question_snapshot, 'id');
            if ($s->configuration_snapshot['shuffle_questions']) {
                shuffle($order);
            }
            $options = [];
            foreach ($s->question_snapshot as $q) {
                $ids = array_column($q['options'], 'id');
                if ($s->configuration_snapshot['shuffle_options']) {
                    shuffle($ids);
                }
                $options[$q['id']] = $ids;
            }
            $a = Attempt::create(['exam_session_id' => $s->id, 'participant_id' => $participantId, 'question_order' => $order, 'option_order' => $options, 'started_at' => now(), 'deadline_at' => $s->ends_at, 'last_seen_at' => now()]);
            $this->log('attempt_started', ['attempt_id' => $a->id]);

            return $a;
        });
    }

    public function save(Attempt $attempt, array $data): Attempt
    {
        return DB::transaction(function () use ($attempt, $data) {
            $s = ExamSession::lockForUpdate()->findOrFail($attempt->exam_session_id);
            $a = Attempt::lockForUpdate()->findOrFail($attempt->id);
            if ($a->status !== 'in_progress') {
                return $a;
            }
            if ($s->status !== 'running' || now()->gte($a->deadline_at)) {
                return $this->finalizeLocked($a, $s, 'timed_out');
            }
            $this->require(in_array($data['question_id'], $a->question_order, true), 'Soal tidak termasuk attempt ini.');
            $this->require($data['answer_option_id'] === null || in_array($data['answer_option_id'], $a->option_order[$data['question_id']], true), 'Pilihan tidak sesuai soal.');
            $answer = ParticipantAnswer::firstOrNew(['attempt_id' => $a->id, 'question_id' => $data['question_id']]);
            $this->require($data['revision'] > ($answer->revision ?? 0), 'Revisi jawaban sudah usang. Sinkronkan ulang sebelum menyimpan.');
            $answer->fill($data)->save();
            $a->update(['last_seen_at' => now()]);

            return $a;
        });
    }

    public function submit(Attempt $attempt, string $status = 'submitted'): Attempt
    {
        return DB::transaction(function () use ($attempt, $status) {
            $s = ExamSession::lockForUpdate()->findOrFail($attempt->exam_session_id);
            $a = Attempt::lockForUpdate()->findOrFail($attempt->id);
            if ($a->status !== 'in_progress') {
                return $a;
            }

            return $this->finalizeLocked($a, $s, now()->gte($a->deadline_at) || $s->status !== 'running' ? 'timed_out' : $status);
        });
    }

    private function finalizeLocked(Attempt $a, ExamSession $s, string $status): Attempt
    {
        $answers = $a->answers()->get()->keyBy('question_id');
        $correct = 0;
        $incorrect = 0;
        $blank = 0;
        foreach ($s->question_snapshot as $q) {
            $selected = $answers->get($q['id'])?->answer_option_id;
            if (! $selected) {
                $blank++;

                continue;
            }
            $option = collect($q['options'])->firstWhere('id', $selected);
            if ($option && $option['is_correct']) {
                $correct++;
            } else {
                $incorrect++;
            }
        }
        $c = $s->configuration_snapshot;
        Result::create(['attempt_id' => $a->id, 'correct_count' => $correct, 'incorrect_count' => $incorrect, 'blank_count' => $blank, 'total_score' => $correct * $c['correct_score'] + $incorrect * $c['incorrect_score'] + $blank * $c['blank_score']]);
        $a->update(['status' => $status, 'submitted_at' => $status === 'timed_out' ? $a->deadline_at : now()]);
        $this->log($status === 'timed_out' ? 'attempt_auto_submitted' : 'attempt_submitted', ['attempt_id' => $a->id]);

        return $a;
    }

    public function expire(): void
    {
        ExamSession::where('status', 'running')->where('ends_at', '<=', now())->pluck('id')->each(function ($id) {
            $this->endSession(ExamSession::findOrFail($id), true);
        });
    }

    public function endSession(ExamSession $session, bool $expired = false): void
    {
        DB::transaction(function () use ($session, $expired) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            if ($s->status !== 'running') {
                return;
            }
            $cutoff = $expired ? $s->ends_at : now();
            $s->update(['status' => 'ended', 'ends_at' => $cutoff]);
            foreach ($s->attempts()->where('status', 'in_progress')->lockForUpdate()->get() as $a) {
                $a->update(['deadline_at' => $cutoff]);
                $this->finalizeLocked($a, $s, 'timed_out');
            }
            $this->log('session_ended', ['exam_session_id' => $s->id]);
        });
    }

    public function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['exam' => $message]);
        }
    }

    public function leaderboard(ExamSession $s)
    {
        $rows = Result::with('attempt.participant')->whereHas('attempt', fn ($q) => $q->where('exam_session_id', $s->id))->get();
        $tie = $s->configuration_snapshot['tie_breaker'] ?? 'none';
        $rows = $rows->sort(function ($a, $b) use ($tie) {
            $score = (float) $b->total_score <=> (float) $a->total_score;
            if ($score) {
                return $score;
            }

            return match ($tie) {
                'correct_count' => $b->correct_count <=> $a->correct_count,'earliest_submit' => ($a->attempt->submitted_at->getTimestamp() - $a->attempt->started_at->getTimestamp()) <=> ($b->attempt->submitted_at->getTimestamp() - $b->attempt->started_at->getTimestamp()),default => 0
            };
        })->values();
        $rank = 0;
        $previous = null;
        foreach ($rows as $i => $row) {
            $key = $row->total_score.'|'.match ($tie) {
                'correct_count' => $row->correct_count,'earliest_submit' => $row->attempt->submitted_at->getTimestamp() - $row->attempt->started_at->getTimestamp(),default => ''
            };
            if ($key !== $previous) {
                $rank = $i + 1;
            }
            $row->rank = $rank;
            $previous = $key;
        }

        return $rows;
    }
}
