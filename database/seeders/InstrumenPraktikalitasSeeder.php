<?php

namespace Database\Seeders;

use App\Models\InstrumenPraktikalitas;
use Illuminate\Database\Seeder;

class InstrumenPraktikalitasSeeder extends Seeder
{
    /**
     * Jalankan seeder instrumen praktikalitas media.
     */
    public function run(): void
    {
        $butirList = [
            [
                'urutan' => 1,
                'aspek' => 'Tampilan & Antarmuka',
                'pernyataan' => 'Tampilan website menarik, jelas, dan nyaman dilihat di layar.',
            ],
            [
                'urutan' => 2,
                'aspek' => 'Tampilan & Antarmuka',
                'pernyataan' => 'Tata letak tombol, tulisan, dan menu mudah dipahami dan tidak membingungkan.',
            ],
            [
                'urutan' => 3,
                'aspek' => 'Kemudahan Pengoperasian',
                'pernyataan' => 'Navigasi antar halaman bab, materi, dan kuis berjalan lancar tanpa kendala.',
            ],
            [
                'urutan' => 4,
                'aspek' => 'Kemudahan Pengoperasian',
                'pernyataan' => 'Website mudah dan nyaman diakses baik melalui smartphone (HP) maupun komputer.',
            ],
            [
                'urutan' => 5,
                'aspek' => 'Kejelasan Materi & Kuis',
                'pernyataan' => 'Materi pembelajaran dan video ditampilkan dengan jelas serta mudah dipelajari.',
            ],
            [
                'urutan' => 6,
                'aspek' => 'Kejelasan Materi & Kuis',
                'pernyataan' => 'Petunjuk kuis, penghitung waktu (timer), dan tampilan nilai mudah dipahami.',
            ],
            [
                'urutan' => 7,
                'aspek' => 'Kemanfaatan Media',
                'pernyataan' => 'Website ini membantu saya belajar secara mandiri, runtut, dan terarah.',
            ],
            [
                'urutan' => 8,
                'aspek' => 'Kemanfaatan Media',
                'pernyataan' => 'Secara keseluruhan, saya merasa terbantu dan senang belajar menggunakan website ini.',
            ],
        ];

        foreach ($butirList as $item) {
            InstrumenPraktikalitas::updateOrCreate(
                ['urutan' => $item['urutan']],
                [
                    'aspek' => $item['aspek'],
                    'pernyataan' => $item['pernyataan'],
                ]
            );
        }
    }
}
