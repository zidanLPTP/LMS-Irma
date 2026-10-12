<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_situs' => ['required', 'string', 'max:100'],
            'logo' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:1024', // 1 MB
            ],
            'nilai_lulus' => ['required', 'numeric', 'min:0', 'max:100'],
            'batas_waktu_per_soal' => ['required', 'integer', 'min:10', 'max:600'],
            'maksimal_kesempatan' => ['required', 'integer', 'min:1', 'max:5'],
            'pendaftaran_siswa' => ['nullable', 'in:buka,tutup'],
            'kode_pendaftaran' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_situs.required' => 'Nama situs wajib diisi.',
            'logo.mimes' => 'Format logo hanya boleh berupa PNG, JPG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'nilai_lulus.required' => 'Nilai kelulusan wajib diisi.',
            'nilai_lulus.min' => 'Nilai kelulusan minimal 0.',
            'nilai_lulus.max' => 'Nilai kelulusan maksimal 100.',
            'batas_waktu_per_soal.required' => 'Batas waktu per soal wajib diisi.',
            'batas_waktu_per_soal.min' => 'Batas waktu per soal minimal 10 detik.',
            'batas_waktu_per_soal.max' => 'Batas waktu per soal maksimal 600 detik.',
            'maksimal_kesempatan.required' => 'Jumlah kesempatan wajib diisi.',
            'maksimal_kesempatan.min' => 'Jumlah kesempatan minimal 1.',
            'maksimal_kesempatan.max' => 'Jumlah kesempatan maksimal 5.',
            'pendaftaran_siswa.in' => 'Status pendaftaran siswa harus berupa buka atau tutup.',
        ];
    }
}
