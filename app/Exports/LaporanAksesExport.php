<?php

namespace App\Exports;

use App\Exports\Sheets\RekapSiswaAksesSheet;
use App\Exports\Sheets\RiwayatAksesSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanAksesExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected ?string $tanggalMulai = null,
        protected ?string $tanggalSelesai = null,
        protected ?string $cari = null
    ) {}

    public function sheets(): array
    {
        return [
            new RiwayatAksesSheet($this->tanggalMulai, $this->tanggalSelesai, $this->cari),
            new RekapSiswaAksesSheet($this->tanggalMulai, $this->tanggalSelesai, $this->cari),
        ];
    }
}
