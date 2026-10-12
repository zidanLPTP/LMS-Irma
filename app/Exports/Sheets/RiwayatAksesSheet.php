<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\LogAkses;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RiwayatAksesSheet extends AmanValueBinder implements FromQuery, WithChunkReading, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        protected ?string $tanggalMulai = null,
        protected ?string $tanggalSelesai = null,
        protected ?string $cari = null
    ) {}

    public function query(): Builder
    {
        return LogAkses::query()
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->where('peran', 'siswa');
                if ($this->cari) {
                    $q->where(function ($sub) {
                        $sub->where('nama_lengkap', 'like', "%{$this->cari}%")
                            ->orWhere('username', 'like', "%{$this->cari}%");
                    });
                }
            })
            ->when($this->tanggalMulai, fn ($q) => $q->where('waktu_masuk', '>=', $this->tanggalMulai.' 00:00:00'))
            ->when($this->tanggalSelesai, fn ($q) => $q->where('waktu_masuk', '<=', $this->tanggalSelesai.' 23:59:59'))
            ->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Kelas',
            'Waktu Masuk',
            'Aktivitas Terakhir',
            'Durasi (Detik)',
            'Durasi (JJ:MM:DD)',
        ];
    }

    /**
     * @param  LogAkses  $row
     */
    public function map($row): array
    {
        $jam = floor($row->durasi_detik / 3600);
        $menit = floor(($row->durasi_detik % 3600) / 60);
        $detik = $row->durasi_detik % 60;
        $formatTeks = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

        $waktuMasukExcel = $row->waktu_masuk
            ? Date::PHPToExcel($row->waktu_masuk->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        $aktivitasTerakhirExcel = $row->aktivitas_terakhir
            ? Date::PHPToExcel($row->aktivitas_terakhir->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        return [
            $row->user?->nama_lengkap ?? '-',
            $row->user?->kelas ?? '-',
            $waktuMasukExcel,
            $aktivitasTerakhirExcel,
            (int) $row->durasi_detik,
            $formatTeks,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => 'dd/mm/yyyy hh:mm:ss',
            'D' => 'dd/mm/yyyy hh:mm:ss',
            'E' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'Riwayat Akses';
    }
}
