<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplatImporSoalExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new class implements FromArray, WithHeadings, WithTitle
            {
                public function array(): array
                {
                    return [
                        [
                            'pilihan_ganda',
                            'Organel sel yang berfungsi sebagai tempat fotosintesis adalah...',
                            10,
                            'Mitokondria',
                            'Kloroplas',
                            'Ribosom',
                            'Badan Golgi',
                            'Retikulum Endoplasma',
                            'B',
                            '',
                            '',
                            '',
                        ],
                        [
                            'isian',
                            'Molekul pembawa materi genetik pada sebagian besar makhluk hidup adalah...',
                            15,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            'DNA',
                            'asam deoksiribonukleat',
                            '',
                        ],
                        [
                            'esai',
                            'Jelaskan perbedaan mendasar antara pembelahan mitosis dan meiosis beserta tujuannya!',
                            25,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                        ],
                    ];
                }

                public function headings(): array
                {
                    return [
                        'tipe',
                        'pertanyaan',
                        'bobot',
                        'pilihan_a',
                        'pilihan_b',
                        'pilihan_c',
                        'pilihan_d',
                        'pilihan_e',
                        'kunci',
                        'kunci_isian_1',
                        'kunci_isian_2',
                        'kunci_isian_3',
                    ];
                }

                public function title(): string
                {
                    return 'Daftar Soal';
                }
            },
            new class implements FromArray, WithHeadings, WithTitle
            {
                public function array(): array
                {
                    return [
                        ['1. Jangan mengubah atau menghapus baris pertama (nama kolom header).'],
                        ['2. Kolom "tipe" hanya boleh diisi: "pilihan_ganda", "isian", atau "esai".'],
                        ['3. Kolom "pertanyaan" dan "bobot" (minimal 1) wajib diisi untuk semua tipe soal.'],
                        ['4. Untuk "pilihan_ganda": isi minimal pilihan_a dan pilihan_b (maksimal hingga pilihan_e). Kolom "kunci" diisi huruf pilihan yang benar (contoh: A, B, C, D, atau E).'],
                        ['5. Untuk "isian": kosongkan pilihan_a sampai pilihan_e dan kolom kunci. Isi minimal kolom "kunci_isian_1" (opsional kunci_isian_2 dan kunci_isian_3 sebagai alternatif penulisan yang benar).'],
                        ['6. Untuk "esai": kosongkan pilihan_a sampai pilihan_e, kolom kunci, dan kunci isian. Penilaian esai akan dilakukan manual oleh guru.'],
                        ['7. Maksimal 100 soal per berkas unggahan (maksimal 1 MB). Seluruh baris harus valid; bila ada satu baris galat maka impor dibatalkan.'],
                    ];
                }

                public function headings(): array
                {
                    return ['Petunjuk Pengisian Impor Soal Kuis'];
                }

                public function title(): string
                {
                    return 'Petunjuk Pengisian';
                }
            },
        ];
    }
}
