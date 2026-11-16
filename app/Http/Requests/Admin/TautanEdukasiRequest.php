<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TautanEdukasiRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->apakahAdmin() ?? false;
    }

    /**
     * Aturan validasi untuk tautan edukasi.
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'url' => ['required', 'url', 'max:500'],
            'urutan' => ['required', 'integer', 'min:1'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Nama atribut dalam bahasa Indonesia.
     */
    public function attributes(): array
    {
        return [
            'judul' => 'judul tautan',
            'deskripsi' => 'deskripsi tautan',
            'url' => 'alamat tautan (URL)',
            'urutan' => 'urutan tampilan',
            'aktif' => 'status aktif',
        ];
    }
}
