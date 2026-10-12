<?php

namespace App\Exports;

use App\Exports\Sheets\DetailPercobaanSheet;
use App\Exports\Sheets\RekapNilaiSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanNilaiExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected ?string $cari = null,
        protected ?string $kelas = null
    ) {}

    public function sheets(): array
    {
        return [
            new RekapNilaiSheet($this->cari, $this->kelas),
            new DetailPercobaanSheet($this->cari, $this->kelas),
        ];
    }
}
