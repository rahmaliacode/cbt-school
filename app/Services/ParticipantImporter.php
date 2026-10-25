<?php

namespace App\Services;

use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ParticipantImporter
{
    public const HEADERS = ['Nomor Peserta', 'Nama', 'Asal Sekolah'];

    // Committee files often use upper-case headers ("NOMOR PESERTA").
    public static function isHeader(array $row): bool
    {
        return array_map(fn ($v) => mb_strtolower(trim((string) $v)), array_slice($row, 0, 3)) === array_map('mb_strtolower', self::HEADERS);
    }

    public function file(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheets = $reader->listWorksheetInfo($path);
        if (! $sheets || $sheets[0]['totalRows'] > 5001) {
            throw ValidationException::withMessages(['file' => 'Maksimal 5000 peserta.']);
        }
        $reader->setLoadSheetsOnly($sheets[0]['worksheetName']);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= 5001 && in_array($columnAddress, ['A', 'B', 'C'], true);
            }
        });
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, false, false, false);
        if (! self::isHeader(array_shift($rows) ?? [])) {
            throw ValidationException::withMessages(['file' => 'Header harus sesuai template peserta.']);
        }

        return $this->validateRows($rows);
    }

    public function validateRows(array $rows, int $firstRow = 2): array
    {
        if (count($rows) > 5000) {
            throw ValidationException::withMessages(['import' => 'Maksimal 5000 peserta.']);
        }
        $valid = $errors = $seen = [];
        foreach ($rows as $i => $row) {
            [$number, $name, $school] = array_pad(array_map(fn ($v) => trim((string) $v), array_slice($row, 0, 3)), 3, '');
            if ($number === '' && $name === '' && $school === '') {
                continue;
            }
            $problems = [];
            if ($number === '' || mb_strlen($number) > 50) {
                $problems[] = 'Nomor peserta wajib diisi, maksimal 50 karakter';
            }
            if ($name === '' || mb_strlen($name) > 150) {
                $problems[] = 'Nama wajib diisi, maksimal 150 karakter';
            }
            if (mb_strlen($school) > 150) {
                $problems[] = 'Asal sekolah maksimal 150 karakter';
            }
            $key = mb_strtolower($number);
            if (isset($seen[$key]) || Participant::whereRaw('LOWER(participant_number) = ?', [$key])->exists()) {
                $problems[] = 'Nomor peserta duplikat';
            }
            $seen[$key] = true;
            if ($problems) {
                $errors[] = ['row' => $i + $firstRow, 'message' => implode('; ', $problems)];
            } else {
                $valid[] = ['participant_number' => $number, 'name' => $name, 'school' => $school ?: null];
            }
        }
        if (! $valid && ! $errors) {
            $errors[] = ['row' => $firstRow, 'message' => 'Tidak ada peserta untuk diimpor.'];
        }

        return ['rows' => $valid, 'errors' => $errors];
    }

    public function commit(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            // Revalidate against the latest master data; the unique index also protects concurrent imports.
            $preview = $this->validateRows(array_map(fn ($r) => [$r['participant_number'], $r['name'], $r['school']], $rows));
            if ($preview['errors']) {
                throw ValidationException::withMessages(['import' => 'Data berubah sejak preview. Ulangi import peserta.']);
            }
            foreach ($preview['rows'] as $row) {
                Participant::create($row);
            }
        });
    }
}
