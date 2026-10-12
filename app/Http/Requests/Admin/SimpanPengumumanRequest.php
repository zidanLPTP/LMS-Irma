<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'status' => ['required', 'in:draf,terbit'],
            'disematkan' => ['nullable', 'boolean'],
            'tanggal_kedaluwarsa' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'judul.max' => 'Judul pengumuman maksimal 255 karakter.',
            'isi.required' => 'Isi pengumuman wajib diisi.',
            'status.required' => 'Status pengumuman wajib dipilih.',
            'status.in' => 'Status pengumuman tidak valid.',
            'tanggal_kedaluwarsa.date' => 'Format tanggal kedaluwarsa tidak valid.',
        ];
    }
}
