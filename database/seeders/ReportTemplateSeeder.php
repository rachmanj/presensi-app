<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ReportTemplate::updateOrCreate(
            ['name' => 'STAFF_HO'],
            [
                'site_profile' => 'office',
                'column_layout' => [
                    'frozen' => ['No', 'Nama', 'NIK', 'Position'],
                    'date_columns' => 30,
                    'summary_groups' => [
                        ['label' => 'LEMBUR STAFF', 'columns' => ['HOS2', 'HOA2', 'TOTAL']],
                        ['columns' => ['1901', '1902', '1903', '1904', '1905', '1906']],
                        ['columns' => ['SC1', 'TOTAL']],
                    ],
                ],
                'footer_config' => [
                    'include_overtime_hours' => true,
                    'keterangan' => [
                        'Terlambat',
                        'Tidak Finger Print Masuk',
                        'Tidak Finger Print Keluar',
                        'ID Ketinggalan',
                        'Visit ke APS',
                        'Belum ada Berkas Pendukung (LOT/Form Cuti/Surat Sakit)',
                        'Belum ada kabar',
                    ],
                ],
                'signature_config' => [
                    'blocks' => ['Prepared,', 'Checked,', 'Approved,'],
                    'doc_no' => 'ARKA/HCS/IV/02.01',
                    'rev' => 'Rev.1',
                ],
            ]
        );

        ReportTemplate::updateOrCreate(
            ['name' => 'STAFF_APS'],
            [
                'site_profile' => 'support',
                'column_layout' => [
                    'title' => 'ABSENSI KARYAWAN PERIODE {from} - {to} {bulan_tahun} (ARKA PROJECT SUPPORT# APS)',
                    'frozen' => ['NO', 'Nama', 'NIK', 'Position'],
                    'date_columns' => 30,
                    'summary_groups' => [
                        ['columns' => ['Sabtu', 'HOS2', 'HOA2', '1901', '1902', '1903', '1904', '1905', '1906', 'SCB', 'Kosong', 'TOTAL', 'HARI KERJA']],
                    ],
                ],
                'footer_config' => ['totals_row' => true, 'include_overtime_hours' => true],
                'signature_config' => [
                    'blocks' => ['Prepared by (HR Supervisor APS)', 'Approved By (Project Manager)'],
                    'doc_no' => 'ARKA/HCS/IV/02.01',
                    'rev' => 'Rev.2',
                    'page' => 'Page 1/1',
                ],
            ]
        );
    }
}
