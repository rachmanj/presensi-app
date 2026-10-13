<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HERO project_code → presensi site code
    |--------------------------------------------------------------------------
    |
    | ARKA HERO sometimes uses project codes that differ from site codes in
    | presensi_db (e.g. Head Office employees use 000H in HERO but HO in sites).
    |
    */
    'site_code_aliases' => [
        '000H' => 'HO',
    ],

];
