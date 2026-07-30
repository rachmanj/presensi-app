<?php

namespace App\Services;

use App\Models\EmployeeMap;
use App\Models\FingerprintImport;
use App\Models\FingerprintScan;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FingerprintParser
{
    public const FORMAT_SCANLOG = 'format1_scanlog';

    public const FORMAT_PAIRED = 'format2_paired';

    public function detectFormat(string $path): string
    {
        try {
            $spreadsheet = $this->loadSpreadsheet($path);
            $sheet = $spreadsheet->getSheet(0);
            $headerRow = $this->findHeaderRow($sheet, ['Tanggal scan', 'Tanggal']);

            if ($headerRow === null) {
                throw new \InvalidArgumentException('Cannot detect fingerprint format: header row not found');
            }

            $headers = $this->readRowValues($sheet, $headerRow);

            if ($this->hasColumn($headers, 'I/O')) {
                return self::FORMAT_SCANLOG;
            }

            if ($this->hasColumn($headers, 'Scan masuk')) {
                return self::FORMAT_PAIRED;
            }
        } catch (\Throwable) {
            // BIFF4 legacy files — detect via Python xlrd
            if ($this->detectBiff4Format($path) === 'scanlog') {
                return self::FORMAT_SCANLOG;
            }
        }

        throw new \InvalidArgumentException('Unknown fingerprint format');
    }

    private function detectBiff4Format(string $path): ?string
    {
        $data = $this->runBiff4Parser($path);

        return ($data && ! isset($data['error']) && ($data['total'] ?? 0) > 0) ? 'scanlog' : null;
    }

    private function runBiff4Parser(string $path): ?array
    {
        $python = $this->findPython();
        $script = base_path('scripts/parse_biff4_xls.py');
        $output = shell_exec(escapeshellarg($python).' '.escapeshellarg($script).' '.escapeshellarg($path).' 2>/dev/null');

        if (! $output) {
            return null;
        }

        // xlrd may print warnings before JSON — extract JSON object
        $jsonStart = strpos($output, '{');
        if ($jsonStart === false) {
            return null;
        }

        return json_decode(substr($output, $jsonStart), true);
    }

    public function parseFormat1(string $path, FingerprintImport $import): void
    {
        try {
            $this->parseFormat1Spreadsheet($path, $import);
        } catch (\Throwable $e) {
            $this->parseFormat1Biff4($path, $import);
        }
    }

    private function parseFormat1Spreadsheet(string $path, FingerprintImport $import): void
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getSheet(0);
        $headerRow = $this->findHeaderRow($sheet, ['Tanggal scan']);
        $headers = $this->readRowValues($sheet, $headerRow);
        $colMap = $this->mapColumns($headers);

        $aggregated = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $scanDateRaw = $this->cellValue($sheet, $row, $colMap['Tanggal scan'] ?? $colMap['Tanggal'] ?? 0);
            if ($scanDateRaw === null || $scanDateRaw === '') {
                continue;
            }

            $nip = trim((string) $this->cellValue($sheet, $row, $colMap['NIP'] ?? 0));
            if ($nip === '') {
                continue;
            }

            $scanDate = $this->parseDate($scanDateRaw);
            if ($scanDate === null) {
                continue;
            }

            $key = "{$nip}|{$scanDate}";
            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'raw_pin' => trim((string) $this->cellValue($sheet, $row, $colMap['PIN'] ?? 0)),
                    'raw_nip' => $nip,
                    'raw_name' => trim((string) $this->cellValue($sheet, $row, $colMap['Nama'] ?? 0)),
                    'scan_date' => $scanDate,
                    'check_ins' => [],
                    'check_outs' => [],
                ];
            }

            $io = (float) $this->cellValue($sheet, $row, $colMap['I/O'] ?? 0);
            $time = $this->parseTime($this->cellValue($sheet, $row, $colMap['Jam'] ?? 0));

            if ($time === null) {
                continue;
            }

            if ($io === 1.0) {
                $aggregated[$key]['check_ins'][] = $time;
            } elseif ($io === 2.0) {
                $aggregated[$key]['check_outs'][] = $time;
            }
        }

        $this->persistAggregatedScans($aggregated, $import);
    }

    private function parseFormat1Biff4(string $path, FingerprintImport $import): void
    {
        $data = $this->runBiff4Parser($path);

        if (! $data || isset($data['error'])) {
            throw new \RuntimeException('Failed to parse BIFF4 xls: '.($data['error'] ?? 'unknown error'));
        }

        $aggregated = [];
        foreach ($data['rows'] as $row) {
            $key = "{$row['raw_nip']}|{$row['scan_date']}";
            $aggregated[$key] = [
                'raw_pin' => $row['raw_pin'],
                'raw_nip' => $row['raw_nip'],
                'raw_name' => $row['raw_name'],
                'scan_date' => $row['scan_date'],
                'check_in' => $row['check_in'],
                'check_out' => $row['check_out'],
            ];
        }

        $this->persistAggregatedScans($aggregated, $import);
    }

    private function persistAggregatedScans(array $aggregated, FingerprintImport $import): void
    {
        $matched = 0;
        $unmatched = 0;

        foreach ($aggregated as $data) {
            $nik = $this->resolveNik($data['raw_nip'], $import->site_code);
            if ($nik) {
                $matched++;
            } else {
                $unmatched++;
            }

            FingerprintScan::create([
                'import_id' => $import->id,
                'raw_pin' => $data['raw_pin'] ?: $data['raw_nip'],
                'raw_nip' => $data['raw_nip'],
                'raw_name' => $data['raw_name'] ?: null,
                'scan_date' => $data['scan_date'],
                'check_in' => $data['check_in'] ?? (! empty($data['check_ins']) ? min($data['check_ins']) : null),
                'check_out' => $data['check_out'] ?? (! empty($data['check_outs']) ? max($data['check_outs']) : null),
                'manual_code' => null,
                'source_sheet' => null,
                'extra' => null,
                'resolved_nik' => $nik,
            ]);
        }

        $import->update([
            'rows_total' => count($aggregated),
            'rows_matched' => $matched,
            'rows_unmatched' => $unmatched,
        ]);
    }

    private function findPython(): string
    {
        $candidates = [
            '/home/deahermes/.hermes/hermes-agent/venv/bin/python3',
            trim(shell_exec('which python3 2>/dev/null') ?? ''),
            'python3',
        ];

        foreach (array_filter(array_unique($candidates)) as $path) {
            if (! is_executable($path)) {
                continue;
            }
            $check = shell_exec(escapeshellarg($path).' -c "import xlrd" 2>&1');
            if ($check === '' || $check === null) {
                return $path;
            }
        }

        return '/home/deahermes/.hermes/hermes-agent/venv/bin/python3';
    }

    public function parseFormat2(string $path, FingerprintImport $import): void
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $totalRows = 0;
        $matched = 0;
        $unmatched = 0;

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (! in_array($sheetName, ['30', 'DNC'], true)) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $headerRow = $this->findHeaderRow($sheet, ['Tanggal']);
            if ($headerRow === null) {
                continue;
            }

            $headers = $this->readRowValues($sheet, $headerRow);
            $colMap = $this->mapColumns($headers);
            $highestRow = $sheet->getHighestDataRow();

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $dateRaw = $this->cellValue($sheet, $row, $colMap['Tanggal'] ?? 0);
                if ($dateRaw === null || $dateRaw === '') {
                    continue;
                }

                $nip = trim((string) $this->cellValue($sheet, $row, $colMap['NIP'] ?? 0));
                if ($nip === '') {
                    continue;
                }

                $scanDate = $this->parseDate($dateRaw);
                if ($scanDate === null) {
                    continue;
                }

                $totalRows++;

                $checkInCol = $colMap['Scan masuk'] ?? null;
                $checkOutCol = $colMap['Scan pulang'] ?? null;

                $checkInCell = $checkInCol !== null ? $sheet->getCellByColumnAndRow($checkInCol + 1, $row) : null;
                $checkOutCell = $checkOutCol !== null ? $sheet->getCellByColumnAndRow($checkOutCol + 1, $row) : null;

                $checkInVal = $checkInCell ? $checkInCell->getValue() : null;
                $checkOutVal = $checkOutCell ? $checkOutCell->getValue() : null;

                $manualCode = null;
                $checkIn = null;
                $checkOut = null;

                if ($this->isManualCode($checkInVal)) {
                    $manualCode = $this->normalizeManualCode($checkInVal);
                } elseif ($this->isTimeValue($checkInVal)) {
                    $checkIn = $this->parseTimeString($checkInVal);
                }

                if ($this->isManualCode($checkOutVal)) {
                    $manualCode = $manualCode ?? $this->normalizeManualCode($checkOutVal);
                } elseif ($this->isTimeValue($checkOutVal)) {
                    $checkOut = $this->parseTimeString($checkOutVal);
                }

                $extra = [];
                foreach (['Terlambat/Ijin', 'Tidak Finger Print Masuk', 'Tidak Finger Print Keluar', 'Visit ke HO', 'Belum ada Berkas Pendukung'] as $extraCol) {
                    if (isset($colMap[$extraCol])) {
                        $val = $this->cellValue($sheet, $row, $colMap[$extraCol]);
                        if ($val !== null && $val !== '') {
                            $extra[$extraCol] = $val;
                        }
                    }
                }

                $visitSite = null;
                if (! empty($extra['Visit ke HO'])) {
                    $visitSite = 'HO';
                }

                $nik = $this->resolveNik($nip, $import->site_code);
                if ($nik) {
                    $matched++;
                } else {
                    $unmatched++;
                }

                FingerprintScan::create([
                    'import_id' => $import->id,
                    'raw_pin' => $nip,
                    'raw_nip' => $nip,
                    'raw_name' => trim((string) $this->cellValue($sheet, $row, $colMap['Nama'] ?? 0)) ?: null,
                    'scan_date' => $scanDate,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'manual_code' => $manualCode,
                    'source_sheet' => $sheetName,
                    'extra' => ! empty($extra) ? $extra : null,
                    'resolved_nik' => $nik,
                ]);
            }
        }

        $import->update([
            'rows_total' => $totalRows,
            'rows_matched' => $matched,
            'rows_unmatched' => $unmatched,
        ]);
    }

    public function isTimeValue(mixed $cellValue): bool
    {
        if ($cellValue === null || $cellValue === '') {
            return false;
        }

        if (is_numeric($cellValue)) {
            return false;
        }

        $str = trim((string) $cellValue);

        return (bool) preg_match('/^\d{2}:\d{2}:\d{2}$/', $str);
    }

    public function isManualCode(mixed $cellValue): bool
    {
        if ($cellValue === null || $cellValue === '') {
            return false;
        }

        if (! is_numeric($cellValue)) {
            $str = trim((string) $cellValue);
            if (preg_match('/^(19\d{2})$/', $str, $m)) {
                $code = (int) $m[1];

                return $code >= 1901 && $code <= 1906;
            }

            return false;
        }

        $code = (int) round((float) $cellValue);

        return $code >= 1901 && $code <= 1906;
    }

    public function resolveNik(string $rawNip, string $siteCode): ?string
    {
        $map = EmployeeMap::where('fingerprint_nip', $rawNip)
            ->where('active', true)
            ->when($siteCode, fn ($q) => $q->where(function ($q) use ($siteCode) {
                $q->where('site_code', $siteCode)->orWhereNull('site_code');
            }))
            ->first();

        return $map?->nik;
    }

    private function loadSpreadsheet(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'xls') {
            $reader = new Xls;

            return $reader->load($path);
        }

        return IOFactory::load($path);
    }

    private function findHeaderRow(Worksheet $sheet, array $firstColCandidates): ?int
    {
        $highestRow = min($sheet->getHighestDataRow(), 10);

        for ($row = 1; $row <= $highestRow; $row++) {
            $firstCell = trim((string) $this->cellValue($sheet, $row, 0));
            foreach ($firstColCandidates as $candidate) {
                if (strcasecmp($firstCell, $candidate) === 0) {
                    return $row;
                }
            }
        }

        return null;
    }

    private function readRowValues(Worksheet $sheet, int $row): array
    {
        $values = [];
        $highestCol = $sheet->getHighestDataColumn();
        $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($col = 1; $col <= $colIndex; $col++) {
            $values[] = $this->cellValue($sheet, $row, $col - 1);
        }

        return $values;
    }

    private function mapColumns(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $header) {
            $key = trim((string) $header);
            if ($key !== '') {
                $map[$key] = $idx;
            }
        }

        return $map;
    }

    private function hasColumn(array $headers, string $name): bool
    {
        foreach ($headers as $header) {
            if (strcasecmp(trim((string) $header), $name) === 0) {
                return true;
            }
        }

        return false;
    }

    private function cellValue(Worksheet $sheet, int $row, int $colIndex): mixed
    {
        return $sheet->getCellByColumnAndRow($colIndex + 1, $row)->getValue();
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

                return $date->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        if (is_numeric($value)) {
            try {
                $time = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

                return $time->format('H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->parseTimeString($value);
    }

    private function parseTimeString(mixed $value): ?string
    {
        $str = trim((string) $value);
        if (preg_match('/^(\d{2}:\d{2}:\d{2})/', $str, $m)) {
            return $m[1];
        }

        return null;
    }

    private function normalizeManualCode(mixed $value): string
    {
        if (is_numeric($value)) {
            return (string) (int) round((float) $value);
        }

        return trim((string) $value);
    }
}
