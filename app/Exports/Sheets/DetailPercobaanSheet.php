<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\PercobaanKuis;
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

class DetailPercobaanSheet extends AmanValueBinder implements FromQuery, WithChunkReading, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        protected ?string $cari = null,
        protected ?string $kelas = null
    ) {}

    public function query(): Builder
    {
        return PercobaanKuis::query()
            ->with(['user', 'kuis'])
            ->whereHas('user', function ($q) {
                $q->where('peran', 'siswa');
                if ($this->cari) {
                    $q->where(function ($sub) {
                        $sub->where('nama_lengkap', 'like', "%{$this->cari}%")
                            ->orWhere('username', 'like', "%{$this->cari}%");
                    });
                }
                if ($this->kelas) {
                    $q->where('kelas', $this->kelas);
                }
            })
            ->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Percobaan',
            'Nama Siswa',
            'Kelas',
            'Judul Kuis',
            'Tipe Kuis',
            'Nomor Percobaan',
            'Status Pengerjaan',
            'Status Kelulusan',
            'Esai Menunggu Nilai',
            'Nilai Otomatis',
            'Nilai Akhir',
            'Waktu Mulai',
            'Waktu Selesai',
            'Dibatalkan',
        ];
    }

    /**
     * @param  PercobaanKuis  $row
     */
    public function map($row): array
    {
        $waktuMulaiExcel = $row->waktu_mulai
            ? Date::PHPToExcel($row->waktu_mulai->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        $waktuSelesaiExcel = $row->waktu_selesai
            ? Date::PHPToExcel($row->waktu_selesai->setTimezone('Asia/Jakarta')->toDateTime())
            : null;

        return [
            (int) $row->id,
            $row->user?->nama_lengkap ?? '-',
            $row->user?->kelas ?? '-',
            $row->kuis?->judul ?? '-',
            $row->kuis?->tipe_kuis ?? '-',
            (int) $row->nomor_percobaan,
            $row->status_pengerjaan,
            $row->status_kelulusan ?? '-',
            $row->esai_menunggu_nilai ? 'Ya' : 'Tidak',
            $row->nilai_otomatis !== null ? (float) $row->nilai_otomatis : '-',
            $row->nilai_akhir !== null ? (float) $row->nilai_akhir : ($row->esai_menunggu_nilai ? 'menunggu penilaian esai' : '-'),
            $waktuMulaiExcel,
            $waktuSelesaiExcel,
            $row->dibatalkan ? 'Ya' : 'Tidak',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'L' => 'dd/mm/yyyy hh:mm:ss',
            'M' => 'dd/mm/yyyy hh:mm:ss',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'Detail Percobaan';
    }
}
