<?php

namespace App\Exports;

use App\Exports\Sheets\DetailResponPraktikalitasSheet;
use App\Exports\Sheets\RekapPraktikalitasSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PraktikalitasExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new RekapPraktikalitasSheet,
            new DetailResponPraktikalitasSheet,
        ];
    }
}
