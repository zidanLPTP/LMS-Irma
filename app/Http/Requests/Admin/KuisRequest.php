<?php

namespace App\Http\Requests\Admin;

use App\Models\Kuis;
use Illuminate\Foundation\Http\FormRequest;

class KuisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $kuisId = $this->route('kuis') ? $this->route('kuis')->id : null;

        return [
            'tipe_kuis' => ['required', 'in:pretest,kuis_bab,posttest'],
            'bab_id' => [
                'nullable',
                'required_if:tipe_kuis,kuis_bab',
                'exists:bab,id',
            ],
            'judul' => ['required', 'string', 'max:255'],
            'batas_waktu_per_soal' => ['nullable', 'integer', 'min:10', 'max:600'],
            'nilai_lulus' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'maksimal_kesempatan' => ['nullable', 'integer', 'min:1', 'max:10'],
            'acak_soal' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tipeKuis = $this->input('tipe_kuis');
            $kuisId = $this->route('kuis') ? $this->route('kuis')->id : 0;

            if (in_array($tipeKuis, ['pretest', 'posttest'], true)) {
                $sudahAda = Kuis::where('tipe_kuis', $tipeKuis)
                    ->where('id', '!=', $kuisId)
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add('tipe_kuis', "Hanya boleh ada satu {$tipeKuis} di dalam sistem.");
                }
            }
        });
    }
}
