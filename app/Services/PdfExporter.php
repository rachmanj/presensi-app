<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\ReportTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PdfExporter
{
    public function export(AttendanceSheet $sheet): string
    {
        $sheet->load(['period', 'reportTemplate', 'rows.cells']);
        $html = view('exports.attendance-sheet', [
            'sheet' => $sheet,
            'period' => $sheet->period,
            'template' => $sheet->reportTemplate,
            'rows' => $sheet->rows,
            'header' => $this->renderHeader($sheet),
            'tableHtml' => $this->renderTable($sheet->rows, $sheet->reportTemplate, $sheet->period),
            'signatureHtml' => $this->renderSignature($sheet),
            'isDraft' => $sheet->status !== 'finalized',
        ])->render();

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/'.uniqid("sheet_{$sheet->id}_", true).'.pdf';

        Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->save($path);

        return $path;
    }

    public function renderHeader(AttendanceSheet $sheet): string
    {
        $period = $sheet->period;
        $template = $sheet->reportTemplate;
        $title = $template?->column_layout['title'] ?? 'LAPORAN ABSENSI';
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $periodLabel = ($months[$period->month] ?? '').' '.$period->year;

        return "<h2>{$title}</h2><p><strong>Site:</strong> {$sheet->site_code} &nbsp; <strong>Periode:</strong> {$periodLabel}</p>";
    }

    public function renderTable(Collection $rows, ReportTemplate $template, AttendancePeriod $period): string
    {
        $daysInMonth = Carbon::create($period->year, $period->month, 1)->daysInMonth;
        $layout = $template->column_layout;
        $dateCount = $layout['date_columns'] ?? $daysInMonth;
        $includeOvertime = ($template->footer_config['include_overtime_hours'] ?? false);

        $html = '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse;font-size:8px;width:100%">';
        $html .= '<thead><tr><th>No</th><th>Nama</th><th>NIK</th><th>Position</th>';

        for ($d = 1; $d <= $dateCount; $d++) {
            $html .= "<th>{$d}</th>";
        }

        $summaryCols = $this->getFlatSummaryColumns($layout);
        foreach ($summaryCols as $col) {
            $html .= '<th>'.htmlspecialchars($col).'</th>';
        }

        if ($includeOvertime) {
            $html .= '<th>JAM LEMBUR</th>';
        }

        $html .= '</tr></thead><tbody>';

        $no = 1;
        foreach ($rows as $row) {
            $cellsByDay = $row->cells->keyBy('day_of_month');
            $html .= '<tr>';
            $html .= '<td>'.$no++.'</td>';
            $html .= '<td>'.htmlspecialchars($row->employee_name).'</td>';
            $html .= '<td>'.htmlspecialchars($row->nik).'</td>';
            $html .= '<td>'.htmlspecialchars($row->position ?? '').'</td>';

            for ($d = 1; $d <= $dateCount; $d++) {
                $cell = $cellsByDay->get($d);
                $code = $cell ? ($cell->final_code ?? $cell->auto_code) : '';
                $html .= '<td>'.htmlspecialchars($code ?? '').'</td>';
            }

            $summary = $row->summary ?? [];
            foreach ($summaryCols as $col) {
                $html .= '<td>'.($summary[$col] ?? 0).'</td>';
            }

            if ($includeOvertime) {
                $otHours = $row->cells->sum('overtime_hours');
                $html .= '<td>'.round((float) $otHours, 2).'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    public function renderSignature(AttendanceSheet $sheet): string
    {
        $sig = $sheet->reportTemplate?->signature_config ?? [];
        $meta = $sheet->meta ?? [];
        $blocks = $sig['blocks'] ?? ['Dibuat oleh', 'Diperiksa oleh', 'Disetujui oleh'];
        $date = now()->format('d F Y');

        $html = '<div style="margin-top:40px;display:flex;justify-content:space-between">';
        foreach ($blocks as $i => $label) {
            $name = match ($i) {
                0 => $meta['prepared_by'] ?? '',
                1 => $meta['checked_by'] ?? '',
                2 => $meta['approved_by'] ?? '',
                default => '',
            };
            $html .= '<div style="text-align:center;width:30%"><p>'.htmlspecialchars($label).'</p><br><br><p><strong>'.htmlspecialchars($name).'</strong></p><p>'.$date.'</p></div>';
        }
        $html .= '</div>';

        return $html;
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
}
