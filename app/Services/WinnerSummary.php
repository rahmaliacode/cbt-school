<?php
namespace App\Services;
use App\Models\ExamSession;
use App\Models\Subject;
class WinnerSummary {
    public function get(array $selected = []) {
        // Champions come from main sessions; draw sessions only settle ties and keep their own results page.
        $sessions = ExamSession::where('question_set', 'main')->where('status', 'ended')->whereNotNull('results_locked_at')->orderByDesc('results_locked_at')->orderByDesc('id')->get();
        return Subject::orderBy('name')->get()->map(function ($subject) use ($sessions, $selected) {
            $choices = $sessions->where('subject_id', $subject->id)->values();
            $session = $choices->firstWhere('id', $selected[$subject->id] ?? null) ?? $choices->first();
            $rows = $session ? app(ExamEngine::class)->leaderboard($session) : collect();
            return ['subject' => $subject, 'session' => $session, 'choices' => $choices, 'winners' => $rows->where('rank', 1)->values(), 'count' => $rows->count()];
        });
    }
}
