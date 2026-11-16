<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\InstrumenPraktikalitas;
use App\Models\ResponPraktikalitas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapPraktikalitasSheet extends AmanValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Rekap Butir Praktikalitas';
    }

    public function headings(): array
    {
        return [
            'No',
            'Aspek',
            'Pernyataan',
            'Sangat Setuju (4)',
            'Setuju (3)',
            'Tidak Setuju (2)',
            'Sangat Tidak Setuju (1)',
            'Jumlah Responden',
            'Total Skor',
            'Skor Maksimum',
            'Persentase (%)',
            'Kategori Kepraktisan',
        ];
    }

    public function collection(): Collection
    {
        $instrumenList = InstrumenPraktikalitas::with('respon')->orderBy('urutan')->get();

        $rows = collect();
        $totalSkorSemua = 0;
        $totalMaksSemua = 0;

        foreach ($instrumenList as $item) {
            $respon = $item->respon;
            $n = $respon->count();
            $s4 = $respon->where('skor', 4)->count();
            $s3 = $respon->where('skor', 3)->count();
            $s2 = $respon->where('skor', 2)->count();
            $s1 = $respon->where('skor', 1)->count();

            $totalSkor = $respon->sum('skor');
            $skorMaks = $n * 4;
            $persen = $skorMaks > 0 ? round(($totalSkor / $skorMaks) * 100, 2) : 0;

            $totalSkorSemua += $totalSkor;
            $totalMaksSemua += $skorMaks;

            $kategori = match (true) {
                $persen >= 85 => 'Sangat Praktis',
                $persen >= 70 => 'Praktis',
                $persen >= 50 => 'Cukup Praktis',
                default => 'Kurang Praktis',
            };

            $rows->push([
                $item->urutan,
                $item->aspek,
                $item->pernyataan,
                $s4,
                $s3,
                $s2,
                $s1,
                $n,
                $totalSkor,
                $skorMaks,
                $persen . '%',
                $kategori,
            ]);
        }

        $persenTotal = $totalMaksSemua > 0 ? round(($totalSkorSemua / $totalMaksSemua) * 100, 2) : 0;
        $kategoriTotal = match (true) {
            $persenTotal >= 85 => 'Sangat Praktis',
            $persenTotal >= 70 => 'Praktis',
            $persenTotal >= 50 => 'Cukup Praktis',
            default => 'Kurang Praktis',
        };

        // Baris ringkasan total
        $rows->push([
            '',
            'TOTAL / KESELURUHAN',
            '',
            '',
            '',
            '',
            '',
            '',
            $totalSkorSemua,
            $totalMaksSemua,
            $persenTotal . '%',
            $kategoriTotal,
        ]);

        return $rows;
    }
}
