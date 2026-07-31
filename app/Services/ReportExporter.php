<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportExporter
{
    public function export(AttendanceSheet $sheet): string
    {
        $template = $sheet->reportTemplate;
        $period = $sheet->period;
        $rows = $sheet->rows()->with('cells')->orderBy('id')->get();

        $tempPath = storage_path('app/exports/'.uniqid("sheet_{$sheet->id}_", true).'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Writer;
        $writer->openToFile($tempPath);

        $headerStyle = new Style(fontBold: true, cellAlignment: CellAlignment::CENTER);

        foreach ($this->buildHeaderRows($template, $period) as $headerRow) {
            $writer->addRow(Row::fromValuesWithStyle($headerRow, $headerStyle));
        }

        $no = 1;
        foreach ($rows as $row) {
            $dataRow = $this->buildDataRow($row, $no++, $template, $period);
            $writer->addRow(Row::fromValues($dataRow));
        }

        if ($template->footer_config['totals_row'] ?? false) {
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle($this->buildTotalsRow($rows, $template, $period), $headerStyle));
        }

        foreach ($this->buildSignatureRows($sheet, $template) as $sigRow) {
            $writer->addRow(Row::fromValues($sigRow));
        }

        foreach ($this->buildFooterRows($template) as $footerRow) {
            $writer->addRow(Row::fromValues($footerRow));
        }

        $writer->close();

        return $tempPath;
    }

    private function buildHeaderRows(ReportTemplate $template, AttendancePeriod $period): array
    {
        $layout = $template->column_layout;
        $daysInMonth = Carbon::create($period->year, $period->month, 1)->daysInMonth;
        $dateCount = $layout['date_columns'] ?? $daysInMonth;
        $frozen = $layout['frozen'] ?? ['No', 'Nama', 'NIK', 'Position'];
        $summaryCols = $this->getFlatSummaryColumns($layout);

        $isAps = ($template->name === 'STAFF_APS');

        if ($isAps) {
            $row1 = array_merge(
                ['', '', '', ''],
                [$this->formatApsTitle($layout, $period)],
            );
            $row1 = $this->padRow($row1, 4 + $dateCount + count($summaryCols));

            $row2 = [];
            $row3 = array_merge($frozen, [''], $summaryCols);
            $row3 = $this->padRow($row3, 4 + $dateCount + count($summaryCols));

            $row4 = array_merge(
                $frozen,
                range(1, $dateCount),
                $summaryCols,
            );

            return [$row1, $row2, $row3, $row4];
        }

        $row1 = [];
        $row2 = [];
        $row3 = array_merge($frozen, [''], array_fill(0, count($summaryCols), ''));
        $row3[4] = Carbon::create($period->year, $period->month, 1)->format('Y-m-d');

        $row4 = array_merge(
            $frozen,
            range(1, $dateCount),
            $this->buildSummaryHeaderRow($layout),
        );

        return [$row1, $row2, $row3, $row4];
    }

    private function buildSummaryHeaderRow(array $layout): array
    {
        $headers = [];
        $spacerAdded = false;

        foreach ($layout['summary_groups'] ?? [] as $group) {
            if (! $spacerAdded && ! empty($headers)) {
                $headers[] = '';
                $spacerAdded = true;
            }

            if (isset($group['label'])) {
                $headers[] = $group['label'];
                foreach (array_slice($group['columns'] ?? [], 1) as $col) {
                    $headers[] = '';
                }
            } else {
                foreach ($group['columns'] ?? [] as $col) {
                    $headers[] = $col;
                }
            }
        }

        $flat = [];
        foreach ($layout['summary_groups'] ?? [] as $group) {
            if (isset($group['label'])) {
                foreach ($group['columns'] ?? [] as $col) {
                    $flat[] = $col;
                }
            } else {
                foreach ($group['columns'] ?? [] as $col) {
                    $flat[] = $col;
                }
            }
        }

        return $flat;
    }

    private function buildDataRow(AttendanceRow $row, int $no, ReportTemplate $template, AttendancePeriod $period): array
    {
        $layout = $template->column_layout;
        $daysInMonth = Carbon::create($period->year, $period->month, 1)->daysInMonth;
        $dateCount = $layout['date_columns'] ?? $daysInMonth;

        $cellsByDay = $row->cells->keyBy('day_of_month');
        $dateCells = [];

        for ($d = 1; $d <= $dateCount; $d++) {
            $cell = $cellsByDay->get($d);
            $code = $cell ? ($cell->final_code ?? $cell->auto_code) : null;
            $dateCells[] = ($code !== null && $code !== '') ? $code : '';
        }

        $summary = $this->buildSummaryColumns($row, $template);

        $includeOvertime = ($template->footer_config['include_overtime_hours'] ?? false);
        if ($includeOvertime) {
            $otHours = round((float) $row->cells->sum('overtime_hours'), 2);
            $summary[] = $otHours;
        }

        return array_merge(
            [$no, $row->employee_name, $row->nik, $row->position ?? ''],
            $dateCells,
            [''],
            $summary,
        );
    }

    private function buildSummaryColumns(AttendanceRow $row, ReportTemplate $template): array
    {
        $summary = $row->summary ?? [];
        $columns = $this->getFlatSummaryColumns($template->column_layout);
        $result = [];

        foreach ($columns as $col) {
            if ($col === 'TOTAL' && ($template->column_layout['summary_groups'][0]['label'] ?? '') === 'LEMBUR STAFF') {
                $result[] = ($summary['HOS2'] ?? 0) + ($summary['HOA2'] ?? 0);
            } elseif ($col === 'TOTAL' && count($this->getFlatSummaryColumns($template->column_layout)) > 3) {
                $result[] = array_sum(array_filter($summary, 'is_numeric'));
            } else {
                $result[] = $summary[$col] ?? 0;
            }
        }

        return $result;
    }

    private function buildTotalsRow(Collection $rows, ReportTemplate $template, AttendancePeriod $period): array
    {
        $layout = $template->column_layout;
        $dateCount = $layout['date_columns'] ?? 30;
        $summaryCols = $this->getFlatSummaryColumns($layout);
        $totals = array_fill(0, count($summaryCols), 0);

        foreach ($rows as $row) {
            $summary = $this->buildSummaryColumns($row, $template);
            foreach ($summary as $i => $val) {
                if (is_numeric($val)) {
                    $totals[$i] += $val;
                }
            }
        }

        return array_merge(
            ['', 'TOTAL', '', ''],
            array_fill(0, $dateCount, ''),
            [''],
            $totals,
        );
    }

    private function buildFooterRows(ReportTemplate $template): array
    {
        $rows = [];
        $keterangan = $template->footer_config['keterangan'] ?? [];

        if (empty($keterangan)) {
            return $rows;
        }

        $rows[] = ['Keterangan'];
        foreach ($keterangan as $item) {
            $rows[] = ['', $item];
        }

        return $rows;
    }

    private function buildSignatureRows(AttendanceSheet $sheet, ReportTemplate $template): array
    {
        $sig = $template->signature_config ?? [];
        $meta = $sheet->meta ?? [];
        $blocks = $sig['blocks'] ?? [];
        $rows = [];

        if (! empty($blocks)) {
            $labelRow = array_fill(0, 48, '');
            $labelRow[1] = $blocks[0] ?? '';
            if (isset($blocks[1])) {
                $labelRow[16] = $blocks[1];
            }
            if (isset($blocks[2])) {
                $labelRow[29] = $blocks[2];
            }
            $rows[] = $labelRow;

            $rows[] = [];
            $rows[] = [];

            $nameRow = array_fill(0, 48, '');
            $nameRow[1] = $meta['prepared_by'] ?? '';
            if (isset($blocks[1])) {
                $nameRow[16] = $meta['checked_by'] ?? '';
            }
            if (isset($blocks[2])) {
                $nameRow[29] = $meta['approved_by'] ?? '';
            }
            $rows[] = $nameRow;
        }

        $docRow = array_fill(0, 48, '');
        $docRow[1] = $sig['doc_no'] ?? '';
        if (isset($sig['rev'])) {
            $docRow[16] = $sig['rev'];
        }
        if (isset($sig['page'])) {
            $docRow[33] = $sig['page'];
        }
        $rows[] = $docRow;

        return $rows;
    }

    private function getFlatSummaryColumns(array $layout): array
    {
        $columns = [];
        foreach ($layout['summary_groups'] ?? [] as $group) {
            foreach ($group['columns'] ?? [] as $col) {
                $columns[] = $col;
            }
        }

        return $columns;
    }

    private function formatApsTitle(array $layout, AttendancePeriod $period): string
    {
        $title = $layout['title'] ?? 'ABSENSI KARYAWAN PERIODE {from} - {to} {bulan_tahun}';
        $start = Carbon::create($period->year, $period->month, 1);
        $end = $start->copy()->endOfMonth();
        $months = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];

        return str_replace(
            ['{from}', '{to}', '{bulan_tahun}'],
            [sprintf('%02d', $start->day), sprintf('%02d', $end->day), strtoupper($months[$period->month]).' '.$period->year],
            $title,
        );
    }

    private function padRow(array $row, int $length): array
    {
        while (count($row) < $length) {
            $row[] = '';
        }

        return $row;
    }
}
