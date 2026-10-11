<?php

namespace App\Http\Requests\Admin;

use App\Models\JawabanSiswa;
use Illuminate\Foundation\Http\FormRequest;

class SimpanNilaiEsaiRequest extends FormRequest
{
    /**
     * Otorisasi hanya admin.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Aturan validasi skor penilaian esai.
     */
    public function rules(): array
    {
        return [
            'penilaian' => ['required', 'array'],
            'penilaian.*.poin' => ['required', 'numeric', 'min:0'],
            'penilaian.*.catatan' => ['nullable', 'string', 'max:1000'],
            'lanjut' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Validasi lanjutan: pastikan poin tidak melebihi bobot_poin soal.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $penilaian = $this->input('penilaian', []);

            foreach ($penilaian as $jawabanId => $data) {
                if (! isset($data['poin']) || ! is_numeric($data['poin'])) {
                    continue;
                }

                $jawaban = JawabanSiswa::with('soal')->find($jawabanId);
                if (! $jawaban || ! $jawaban->soal) {
                    continue;
                }

                $bobotMaksimal = (float) $jawaban->soal->bobot_poin;
                $poinInput = (float) $data['poin'];

                if ($poinInput > $bobotMaksimal) {
                    $v->errors()->add(
                        "penilaian.{$jawabanId}.poin",
                        trans('kuis.poin_melebihi_bobot', [
                            'bobot' => $bobotMaksimal,
                        ])
                    );
                }
            }
        });
    }

    /**
     * Pesan kesalahan ramah Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'penilaian.required' => 'Daftar penilaian esai harus diisi.',
            'penilaian.*.poin.required' => 'Nilai poin wajib diisi.',
            'penilaian.*.poin.numeric' => 'Nilai poin harus berupa angka.',
            'penilaian.*.poin.min' => 'Nilai poin minimal adalah 0.',
            'penilaian.*.catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
