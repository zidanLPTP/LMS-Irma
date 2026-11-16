<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\InstrumenPraktikalitas;
use App\Models\ResponPraktikalitas;
use App\Models\SaranPraktikalitas;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailResponPraktikalitasSheet extends AmanValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Data Respon Siswa';
    }

    public function headings(): array
    {
        $instrumen = InstrumenPraktikalitas::orderBy('urutan')->get();

        $headers = [
            'No',
            'Nama Siswa',
            'Username',
            'Kelas',
        ];

        foreach ($instrumen as $item) {
            $headers[] = "B{$item->urutan} (" . substr($item->aspek, 0, 15) . '...)';
        }

        $headers[] = 'Total Skor';
        $headers[] = 'Rata-rata';
        $headers[] = 'Persentase (%)';
        $headers[] = 'Kategori';
        $headers[] = 'Saran / Masukan Siswa';
        $headers[] = 'Waktu Pengisian';

        return $headers;
    }

    public function collection(): Collection
    {
        $instrumenList = InstrumenPraktikalitas::orderBy('urutan')->get();
        $jumlahButir = $instrumenList->count();

        // Ambil ID siswa yang telah mengisi
        $userIds = ResponPraktikalitas::distinct()->pluck('user_id');

        $users = User::whereIn('id', $userIds)
            ->orderBy('nama_lengkap')
            ->get();

        $semuaRespon = ResponPraktikalitas::whereIn('user_id', $userIds)->get()->groupBy('user_id');
        $semuaSaran = SaranPraktikalitas::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        $rows = collect();
        $no = 1;

        foreach ($users as $user) {
            $responUser = $semuaRespon->get($user->id, collect())->keyBy('instrumen_id');
            $saranUser = $semuaSaran->get($user->id);

            $baris = [
                $no++,
                $user->nama_lengkap,
                $user->username,
                $user->kelas ?? '-',
            ];

            $totalSkor = 0;
            $waktuTerakhir = null;

            foreach ($instrumenList as $item) {
                $r = $responUser->get($item->id);
                $skor = $r?->skor ?? 0;
                $baris[] = $skor ?: '-';
                $totalSkor += $skor;
                if ($r && (! $waktuTerakhir || $r->created_at > $waktuTerakhir)) {
                    $waktuTerakhir = $r->created_at;
                }
            }

            $skorMaks = $jumlahButir * 4;
            $rataRata = $jumlahButir > 0 ? round($totalSkor / $jumlahButir, 2) : 0;
            $persen = $skorMaks > 0 ? round(($totalSkor / $skorMaks) * 100, 2) : 0;

            $kategori = match (true) {
                $persen >= 85 => 'Sangat Praktis',
                $persen >= 70 => 'Praktis',
                $persen >= 50 => 'Cukup Praktis',
                default => 'Kurang Praktis',
            };

            $baris[] = $totalSkor;
            $baris[] = $rataRata;
            $baris[] = $persen . '%';
            $baris[] = $kategori;
            $baris[] = $saranUser?->saran ?? '-';
            $baris[] = $waktuTerakhir ? $waktuTerakhir->translatedFormat('d M Y H:i') : '-';

            $rows->push($baris);
        }

        return $rows;
    }
}
