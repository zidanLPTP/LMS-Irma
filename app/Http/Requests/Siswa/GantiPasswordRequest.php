<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class GantiPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password_lama' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! Hash::check($value, $this->user()->password)) {
                    $fail('Kata sandi saat ini tidak cocok.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_lama.required' => 'Kata sandi lama wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}
