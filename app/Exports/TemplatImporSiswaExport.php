<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplatImporSiswaExport implements Export, WithMultipleSheets
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
                            'Ahmad Pratama',
                            'ahmad_pratama',
                            'ahmad.pratama@siswa.test',
                            'XI MIPA 1',
                        ],
                        [
                            'Budi Santoso',
                            'budi_santoso',
                            'budi.santoso@siswa.test',
                            'XI MIPA 1',
                        ],
                    ];
                }

                public function headings(): array
                {
                    return [
                        'nama_lengkap',
                        'username',
                        'email',
                        'kelas',
                    ];
                }

                public function title(): string
                {
                    return 'Data Siswa';
                }
            },
            new class implements FromArray, WithHeadings, WithTitle
            {
                public function array(): array
                {
                    return [
                        ['1. Jangan mengubah nama kolom pada baris pertama (header).'],
                        ['2. Kolom nama_lengkap, username, dan email wajib diisi.'],
                        ['3. Username hanya boleh huruf, angka, tanda strip (-), atau garis bawah (_).'],
                        ['4. Email harus berformat valid dan belum terdaftar di sistem.'],
                        ['5. Kolom kelas bersifat opsional (contoh: XI MIPA 1).'],
                        ['6. Maksimal 200 baris data per berkas unggahan (maksimal 1 MB).'],
                        ['7. Setelah konfirmasi, setiap siswa otomatis dibuatkan kata sandi acak sementara dan diwajibkan mengganti kata sandi saat pertama kali masuk.'],
                    ];
                }

                public function headings(): array
                {
                    return ['Petunjuk Pengisian Impor Siswa'];
                }

                public function title(): string
                {
                    return 'Petunjuk Pengisian';
                }
            },
        ];
    }
}
