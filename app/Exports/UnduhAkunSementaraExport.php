<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class UnduhAkunSementaraExport extends AmanValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected Collection|array $dataAkun) {}

    public function collection(): Collection
    {
        return collect($this->dataAkun)->map(function ($item) {
            return [
                'nama_lengkap' => $item['nama_lengkap'] ?? '',
                'username' => $item['username'] ?? '',
                'email' => $item['email'] ?? '',
                'kelas' => $item['kelas'] ?? '',
                'kata_sandi_sementara' => $item['kata_sandi_sementara'] ?? ($item['sandi'] ?? ''),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Username',
            'Email',
            'Kelas',
            'Kata Sandi Sementara',
        ];
    }

    public function title(): string
    {
        return 'Akun Siswa Baru';
    }
}
