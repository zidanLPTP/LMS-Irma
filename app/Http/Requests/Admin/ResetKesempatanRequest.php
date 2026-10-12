<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResetKesempatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'kuis_id' => ['required', 'exists:kuis,id'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'kuis_id.required' => 'Kuis wajib dipilih.',
            'kuis_id.exists' => 'Kuis tidak ditemukan.',
            'alasan.required' => 'Alasan reset kesempatan wajib diisi.',
            'alasan.min' => 'Alasan minimal 5 karakter.',
        ];
    }
}
