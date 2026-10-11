<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $soal = $this->route('soal');
        $punyaJawaban = $soal && $soal->jawabanSiswa()->exists();

        return [
            'tipe_soal' => [$punyaJawaban ? 'nullable' : 'required', 'in:pilihan_ganda,isian,esai'],
            'pertanyaan' => ['required', 'string'],
            'bobot_poin' => [$punyaJawaban ? 'nullable' : 'required', 'integer', 'min:1'],
            'pilihan' => ['nullable', 'array'],
            'pilihan.*.teks' => ['nullable', 'string'],
            'pilihan.*.apakah_benar' => ['nullable'],
            'kunci_isian' => ['nullable', 'array'],
            'kunci_isian.*' => ['nullable', 'string'],
            'pilihan_terkunci' => ['nullable', 'array'],
            'pilihan_terkunci.*' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $soal = $this->route('soal');
            if ($soal && $soal->jawabanSiswa()->exists()) {
                return;
            }

            $tipe = $this->input('tipe_soal');

            if ($tipe === 'pilihan_ganda') {
                $pilihan = (array) $this->input('pilihan', []);
                $pilihanTerisi = array_filter($pilihan, fn ($p) => ! empty(trim((string) ($p['teks'] ?? ''))));

                if (count($pilihanTerisi) < 2) {
                    $validator->errors()->add('pilihan', 'Soal pilihan ganda wajib memiliki minimal 2 opsi pilihan.');
                }

                $jumlahBenar = count(array_filter($pilihanTerisi, fn ($p) => ! empty($p['apakah_benar'])));
                if ($jumlahBenar !== 1) {
                    $validator->errors()->add('pilihan', 'Soal pilihan ganda wajib memiliki tepat satu pilihan yang benar.');
                }
            } elseif ($tipe === 'isian') {
                $kunci = (array) $this->input('kunci_isian', []);
                $kunciTerisi = array_filter($kunci, fn ($k) => ! empty(trim((string) $k)));

                if (count($kunciTerisi) < 1) {
                    $validator->errors()->add('kunci_isian', 'Soal isian wajib memiliki minimal satu kunci jawaban yang benar.');
                }
            }
        });
    }
}
