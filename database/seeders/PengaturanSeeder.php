<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    /**
     * Jalankan seeder konfigurasi pengaturan default.
     */
    public function run(): void
    {
        $dataPengaturan = [
            'nama_situs' => 'LMS Penelitian Biologi',
            'nilai_lulus' => '65.00',
            'batas_waktu_per_soal' => '60',
            'maksimal_kesempatan' => '2',
        ];

        foreach ($dataPengaturan as $kunci => $nilai) {
            Pengaturan::updateOrCreate(
                ['kunci' => $kunci],
                ['nilai' => $nilai]
            );
        }
    }
}
