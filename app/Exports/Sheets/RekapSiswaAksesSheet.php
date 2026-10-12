<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RekapSiswaAksesSheet extends AmanValueBinder implements FromQuery, WithChunkReading, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        protected ?string $tanggalMulai = null,
        protected ?string $tanggalSelesai = null,
        protected ?string $cari = null
    ) {}

    public function query(): Builder
    {
        return User::query()
            ->where('peran', 'siswa')
            ->when($this->cari, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nama_lengkap', 'like', "%{$this->cari}%")
                        ->orWhere('username', 'like', "%{$this->cari}%");
                });
            })
            ->withCount(['logAkses as jumlah_sesi' => function ($q) {
                if ($this->tanggalMulai) {
                    $q->where('waktu_masuk', '>=', $this->tanggalMulai.' 00:00:00');
                }
                if ($this->tanggalSelesai) {
                    $q->where('waktu_masuk', '<=', $this->tanggalSelesai.' 23:59:59');
                }
            }])
            ->withSum(['logAkses as total_durasi' => function ($q) {
                if ($this->tanggalMulai) {
                    $q->where('waktu_masuk', '>=', $this->tanggalMulai.' 00:00:00');
                }
                if ($this->tanggalSelesai) {
                    $q->where('waktu_masuk', '<=', $this->tanggalSelesai.' 23:59:59');
                }
            }], 'durasi_detik')
            ->withMin(['logAkses as akses_pertama' => function ($q) {
                if ($this->tanggalMulai) {
                    $q->where('waktu_masuk', '>=', $this->tanggalMulai.' 00:00:00');
                }
                if ($this->tanggalSelesai) {
                    $q->where('waktu_masuk', '<=', $this->tanggalSelesai.' 23:59:59');
                }
            }], 'waktu_masuk')
            ->withMax(['logAkses as akses_terakhir' => function ($q) {
                if ($this->tanggalMulai) {
                    $q->where('waktu_masuk', '>=', $this->tanggalMulai.' 00:00:00');
                }
                if ($this->tanggalSelesai) {
                    $q->where('waktu_masuk', '<=', $this->tanggalSelesai.' 23:59:59');
                }
            }], 'aktivitas_terakhir')
            ->orderBy('nama_lengkap', 'asc');
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Kelas',
            'Jumlah Sesi',
            'Total Durasi (Detik)',
            'Total Durasi (JJ:MM:DD)',
            'Akses Pertama',
            'Akses Terakhir',
        ];
    }

    /**
     * @param  User  $row
     */
    public function map($row): array
    {
        $durasi = (int) ($row->total_durasi ?? 0);
        $jam = floor($durasi / 3600);
        $menit = floor(($durasi % 3600) / 60);
        $detik = $durasi % 60;
        $formatTeks = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

        $aksesPertamaExcel = $row->akses_pertama
            ? Date::PHPToExcel(Carbon::parse($row->akses_pertama)->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        $aksesTerakhirExcel = $row->akses_terakhir
            ? Date::PHPToExcel(Carbon::parse($row->akses_terakhir)->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        return [
            $row->nama_lengkap,
            $row->kelas ?? '-',
            (int) ($row->jumlah_sesi ?? 0),
            $durasi,
            $formatTeks,
            $aksesPertamaExcel,
            $aksesTerakhirExcel,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'F' => 'dd/mm/yyyy hh:mm:ss',
            'G' => 'dd/mm/yyyy hh:mm:ss',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'Rekap per Siswa';
    }
}
