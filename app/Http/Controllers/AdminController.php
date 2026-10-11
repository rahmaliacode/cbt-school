<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\Question;
use App\Models\Subject;
use App\Services\ExamEngine;
use App\Services\QuestionImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function authenticate(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data + ['is_admin' => true])) {
            return back()->withErrors(['email' => 'Email atau password salah.']);
        }
        $r->session()->regenerate();

        return redirect('/admin');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function dashboard(ExamEngine $engine)
    {
        $engine->expire();

        return view('admin.dashboard', ['sessions' => ExamSession::with('subject')->latest()->get(), 'counts' => ['Sesi' => ExamSession::count(), 'Peserta' => Participant::count(), 'Soal' => Question::count(), 'Sedang mengerjakan' => Attempt::where('status', 'in_progress')->count(), 'Selesai' => Attempt::where('status', '!=', 'in_progress')->count()]]);
    }

    public function subjects()
    {
        return view('admin.subjects', ['subjects' => Subject::withCount('questions')->get()]);
    }

    public function storeSubject(Request $r)
    {
        Subject::create($r->validate(['name' => 'required|string|max:100|unique:subjects']));

        return back()->with('success', 'Mapel ditambahkan.');
    }

    public function participants()
    {
        return view('admin.participants', ['participants' => Participant::orderBy('participant_number')->get()]);
    }

    public function storeParticipant(Request $r)
    {
        Participant::create($r->validate(['participant_number' => 'required|string|max:50|unique:participants', 'name' => 'required|string|max:150', 'school' => 'nullable|string|max:150']));

        return back()->with('success', 'Peserta ditambahkan.');
    }

    public function updateParticipant(Request $r, Participant $participant)
    {
        $participant->update($r->validate(['participant_number' => ['required', 'string', 'max:50', Rule::unique('participants')->ignore($participant)], 'name' => 'required|string|max:150', 'school' => 'nullable|string|max:150']));

        return back()->with('success', 'Peserta diperbarui.');
    }

    public function deleteParticipant(Participant $participant)
    {
        abort_if(Attempt::where('participant_id', $participant->id)->exists(), 422, 'Peserta memiliki riwayat ujian.');
        $participant->delete();

        return back();
    }

    public function questions()
    {
        return view('admin.questions', ['questions' => Question::with('subject', 'options')->orderBy('subject_id')->orderBy('number')->get(), 'subjects' => Subject::all()]);
    }

    public function storeQuestion(Request $r, ExamEngine $engine)
    {
        $this->writeQuestion($r, $engine);

        return back()->with('success', 'Soal disimpan.');
    }

    public function updateQuestion(Request $r, Question $question, ExamEngine $engine)
    {
        $this->writeQuestion($r, $engine, $question);

        return back()->with('success', 'Soal diperbarui.');
    }

    private function writeQuestion(Request $r, ExamEngine $engine, ?Question $question = null)
    {
        $data = $r->validate(['subject_id' => 'required|exists:subjects,id', 'number' => ['required', 'integer', 'min:1', 'max:2147483647', Rule::unique('questions')->where('subject_id', $r->subject_id)->ignore($question?->id)], 'text' => 'required|string', 'options' => 'required|array:A,B,C,D,E', 'options.A' => 'required|string', 'options.B' => 'required|string', 'options.C' => 'required|string', 'options.D' => 'required|string', 'options.E' => 'nullable|string', 'key' => 'required|in:A,B,C,D,E']);
        $engine->require($data['key'] !== 'E' || isset($data['options']['E']) && $data['options']['E'] !== '', 'Opsi E wajib jika kunci E.');
        DB::transaction(function () use ($question, $data) {
            if ($question && ExamSession::whereNotNull('question_snapshot')->get()->contains(fn ($s) => in_array($question->id, array_column($s->question_snapshot, 'id')))) {
                throw ValidationException::withMessages(['question' => 'Soal sudah dipakai. Buat soal baru untuk menjaga riwayat.']);
            }
            $q = $question ?? new Question;
            $q->fill(collect($data)->only(['subject_id', 'number', 'text'])->all())->save();
            $q->options()->delete();
            foreach ($data['options'] as $label => $text) {
                if ($text !== null && $text !== '') {
                    $q->options()->create(['label' => $label, 'text' => $text, 'is_correct' => $label === $data['key']]);
                }
            }
        });
    }

    public function deleteQuestion(Question $question)
    {
        abort_if(ExamSession::whereNotNull('question_snapshot')->get()->contains(fn ($s) => in_array($question->id, array_column($s->question_snapshot, 'id'))), 422, 'Soal sudah dipakai sesi.');
        $question->delete();

        return back();
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $book = new Spreadsheet;
            $book->getActiveSheet()->fromArray([QuestionImporter::HEADERS, [1, 'Matematika', '2 + 2 = ?', '3', '4', '5', '6', '', 'B']]);
            foreach (range('A', 'I') as $col) {
                $book->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
            }
            (new Xlsx($book))->save('php://output');
        }, 'question-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Request $r, QuestionImporter $importer)
    {
        $r->session()->forget('question_import');
        if ($r->hasFile('file')) {
            $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
            try {
                $preview = $importer->file($r->file('file')->getRealPath());
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                return back()->withErrors(['file' => 'File tidak dapat dibaca. Gunakan template Excel yang valid.']);
            }
        } else {
            $data = $r->validate(['paste' => 'required|string|max:2000000']);
            $lines = preg_split('/\r?\n/', trim($data['paste']));
            $rows = array_map(fn ($line) => str_getcsv($line, "\t", '"', ''), $lines);
            $hasHeader = ($rows[0] ?? []) === QuestionImporter::HEADERS;
            if ($hasHeader) {
                array_shift($rows);
            }
            $preview = $importer->validateRows($rows, $hasHeader ? 2 : 1);
        }
        if (! $preview['errors']) {
            $r->session()->put('question_import', $preview['rows']);
        }

        return view('admin.import', compact('preview'));
    }

    public function commitImport(Request $r, QuestionImporter $importer, ExamEngine $engine)
    {
        $rows = $r->session()->get('question_import');
        $engine->require(is_array($rows) && count($rows) > 0, 'Preview import belum valid atau sudah dipakai.');
        $importer->commit($rows);
        $r->session()->forget('question_import');
        $engine->log('questions_imported', ['count' => count($rows)]);

        return redirect('/admin/questions')->with('success', count($rows).' soal berhasil diimpor.');
    }

    public function sessions()
    {
        return view('admin.sessions', ['sessions' => ExamSession::with('subject')->latest()->get()]);
    }

    public function sessionForm(?ExamSession $examSession = null)
    {
        return view('admin.session-form', ['session' => $examSession ?? new ExamSession(['question_count' => 30, 'duration_minutes' => 90, 'option_count' => 4, 'correct_score' => 4, 'incorrect_score' => -1, 'blank_score' => 0, 'tie_breaker' => 'none']), 'subjects' => Subject::all(), 'participants' => Participant::all()]);
    }

    public function saveSession(Request $r, ExamEngine $engine, ?ExamSession $examSession = null)
    {
        $data = $r->validate(['name' => 'required|string|max:150', 'subject_id' => 'required|exists:subjects,id', 'token' => ['required', 'string', 'min:6', 'max:32', Rule::unique('exam_sessions')->ignore($examSession?->id)], 'question_count' => 'required|integer|min:1|max:500', 'duration_minutes' => 'required|integer|min:1|max:600', 'correct_score' => 'required|numeric|between:-10000,10000', 'incorrect_score' => 'required|numeric|between:-10000,10000', 'blank_score' => 'required|numeric|between:-10000,10000', 'option_count' => 'required|in:4,5', 'tie_breaker' => 'required|in:none,correct_count,earliest_submit', 'participant_ids' => 'required|array|min:1', 'participant_ids.*' => 'integer|exists:participants,id']);
        foreach (['shuffle_questions', 'shuffle_options', 'show_live_score'] as $f) {
            $data[$f] = $r->boolean($f);
        }DB::transaction(function () use ($examSession, $data, $engine) {
            $s = $examSession ? ExamSession::lockForUpdate()->findOrFail($examSession->id) : new ExamSession;
            $engine->require(! $s->exists || $s->status === 'draft', 'Konfigurasi sesi yang sudah dimulai tidak dapat diubah.');
            $ids = $data['participant_ids'];
            unset($data['participant_ids']);
            $s->fill($data)->save();
            $s->participants()->sync($ids);
        });

        return redirect('/admin/sessions')->with('success', 'Sesi disimpan.');
    }

    public function startSession(ExamSession $examSession, ExamEngine $engine)
    {
        $engine->startSession($examSession);

        return back()->with('success', 'Sesi dimulai.');
    }

    public function endSession(ExamSession $examSession, ExamEngine $engine)
    {
        $engine->endSession($examSession);

        return back()->with('success', 'Sesi diakhiri; jawaban dikunci.');
    }

    public function monitor(ExamSession $examSession)
    {
        return view('admin.monitor', ['session' => $examSession]);
    }

    public function monitorData(ExamSession $examSession, ExamEngine $engine)
    {
        $engine->expire();
        $examSession->refresh();
        $attempts = $examSession->attempts()->withCount(['answers as answered_count' => fn ($q) => $q->whereNotNull('answer_option_id')])->get()->keyBy('participant_id');
        $rows = $examSession->participants->map(function ($p) use ($attempts, $examSession) {
            $a = $attempts->get($p->id);

            return ['id' => $a?->id, 'participant_number' => $p->participant_number, 'name' => $p->name, 'status' => $a?->status ?? 'not_started', 'progress' => ($a?->answered_count ?? 0).'/'.$examSession->question_count, 'remaining_seconds' => $a && $a->status === 'in_progress' ? max(0, now()->diffInSeconds($a->deadline_at, false)) : 0, 'started_at' => $a?->started_at?->toIso8601String(), 'submitted_at' => $a?->submitted_at?->toIso8601String(), 'connection' => $a ? ($a->last_seen_at && $a->last_seen_at->gt(now()->subSeconds(20)) ? 'Stabil' : ($a->last_seen_at && $a->last_seen_at->gt(now()->subSeconds(60)) ? 'Lambat' : 'Terputus')) : '—'];
        });

        return response()->json(['server_now' => now()->toIso8601String(), 'status' => $examSession->status, 'participants' => $rows]);
    }

    public function results(ExamSession $examSession, ExamEngine $engine)
    {
        $engine->expire();

        return view('admin.results', ['session' => $examSession, 'results' => $engine->leaderboard($examSession)]);
    }

    public function detail(Attempt $attempt, ExamEngine $engine)
    {
        $engine->expire();
        $attempt->refresh();
        abort_if($attempt->status === 'in_progress', 403, 'Hasil belum tersedia.');

        return view('admin.detail', compact('attempt'));
    }

    public function export(ExamSession $examSession, ExamEngine $engine)
    {
        $engine->expire();
        $rows = $engine->leaderboard($examSession);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['rank', 'participant_number', 'name', 'correct_count', 'incorrect_count', 'blank_count', 'total_score', 'started_at', 'submitted_at', 'status']);
            foreach ($rows as $r) {
                $a = $r->attempt;
                $safe = fn ($v) => preg_match('/^[=+@\-]/', (string) $v) ? "'".$v : $v;
                fputcsv($out, [$r->rank, $safe($a->participant->participant_number), $safe($a->participant->name), $r->correct_count, $r->incorrect_count, $r->blank_count, $r->total_score, $a->started_at->toIso8601String(), $a->submitted_at->toIso8601String(), $a->status]);
            }fclose($out);
        }, 'results-'.$examSession->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function reset(Request $r, Attempt $attempt, ExamEngine $engine)
    {
        $data = $r->validate(['reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($attempt, $data, $engine) {
            $s = ExamSession::lockForUpdate()->findOrFail($attempt->exam_session_id);
            $a = Attempt::lockForUpdate()->findOrFail($attempt->id);
            $engine->require($s->status === 'running' && now()->lt($s->ends_at), 'Reset hanya untuk sesi yang masih berjalan.');
            $engine->log('attempt_reset', ['attempt_id' => $a->id, 'participant_id' => $a->participant_id, 'exam_session_id' => $s->id, 'reason' => $data['reason'], 'previous_attempt' => $a->toArray(), 'previous_answers' => $a->answers->toArray(), 'previous_result' => $a->result?->toArray()]);
            $a->delete();
        });

        return $r->expectsJson() ? response()->json(['reset' => true]) : back()->with('success', 'Attempt direset; peserta dapat mulai lagi dengan sisa waktu sesi.');
    }
}
