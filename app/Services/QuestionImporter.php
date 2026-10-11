<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class QuestionImporter
{
    const HEADERS = ['No', 'Mapel', 'Soal', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci'];

    public function file(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheets = $reader->listWorksheetInfo($path);
        if (! $sheets || $sheets[0]['totalRows'] > 5001) {
            throw ValidationException::withMessages(['file' => 'Maksimal 5000 soal.']);
        }
        $reader->setLoadSheetsOnly($sheets[0]['worksheetName']);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= 5001 && in_array($columnAddress, range('A', 'I'), true);
            }
        });
        $book = $reader->load($path);
        $sheet = $book->getActiveSheet();
        if ($sheet->getHighestRow() > 5001) {
            throw ValidationException::withMessages(['file' => 'Maksimal 5000 soal.']);
        }
        $rows = $sheet->toArray(null, false, false, false);
        $header = array_shift($rows);
        if (array_slice($header, 0, 9) !== self::HEADERS) {
            throw ValidationException::withMessages(['file' => 'Header harus sesuai template.']);
        }

        return $this->validateRows($rows);
    }

    public function validateRows(array $rows, int $firstRow = 2): array
    {
        if (count($rows) > 5000) {
            throw ValidationException::withMessages(['import' => 'Maksimal 5000 soal.']);
        }
        $valid = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $i => $row) {
            $line = $i + $firstRow;
            $row = array_pad(array_map(fn ($v) => trim((string) $v), array_slice($row, 0, 9)), 9, '');
            if (! implode('', $row)) {
                continue;
            }
            [$number,$subject,$text,$a,$b,$c,$d,$e,$key] = $row;
            $key = strtoupper($key);
            $problems = [];
            if (! ctype_digit($number) || (int) $number < 1 || (int) $number > 2147483647) {
                $problems[] = 'No harus bilangan bulat positif';
            }
            $subjectRecord = Subject::where('name', $subject)->first();
            if (! $subjectRecord) {
                $problems[] = 'Mapel tidak ditemukan; buat master mapel dahulu';
            }
            if ($text === '') {
                $problems[] = 'Soal wajib diisi';
            }
            if ($a === '' || $b === '' || $c === '' || $d === '') {
                $problems[] = 'Opsi A-D wajib diisi';
            }
            $labels = $e !== '' ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];
            if (! in_array($key, $labels, true)) {
                $problems[] = 'Kunci harus cocok dengan opsi A-D/E';
            }
            $unique = ($subjectRecord?->id ?? $subject).'|'.(int) $number;
            if (isset($seen[$unique]) || Question::whereHas('subject', fn ($q) => $q->where('name', $subject))->where('number', $number)->exists()) {
                $problems[] = 'Nomor soal duplikat pada mapel';
            }
            $seen[$unique] = true;
            if ($problems) {
                $errors[] = ['row' => $line, 'message' => implode('; ', $problems)];

                continue;
            }
            $valid[] = ['number' => (int) $number, 'subject' => $subject, 'text' => $text, 'options' => array_filter(['A' => $a, 'B' => $b, 'C' => $c, 'D' => $d, 'E' => $e], fn ($v) => $v !== ''), 'key' => $key];
        }
        if (! $valid && ! $errors) {
            $errors[] = ['row' => $firstRow, 'message' => 'Tidak ada soal untuk diimpor.'];
        }

        return ['rows' => $valid, 'errors' => $errors];
    }

    public function commit(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $subject = Subject::where('name', $row['subject'])->firstOrFail();
                $subject = Subject::lockForUpdate()->findOrFail($subject->id);
                if (Question::where('subject_id', $subject->id)->where('number', $row['number'])->exists()) {
                    throw ValidationException::withMessages(['import' => 'Data berubah sejak preview; nomor soal sudah ada. Ulangi import.']);
                }
                $q = Question::create(['subject_id' => $subject->id, 'number' => $row['number'], 'text' => $row['text']]);
                foreach ($row['options'] as $label => $text) {
                    $q->options()->create(['label' => $label, 'text' => $text, 'is_correct' => $label === $row['key']]);
                }
            }
        });
    }
}
