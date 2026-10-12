<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UbahProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password_saat_ini' => [
                Rule::requiredIf(fn () => $this->input('email') !== $user->email),
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if ($this->input('email') !== $user->email) {
                        if (! $value || ! Hash::check($value, $user->password)) {
                            $fail('Kata sandi saat ini tidak cocok.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password_saat_ini.required' => 'Kata sandi saat ini wajib diisi bila mengubah alamat email.',
        ];
    }
}
