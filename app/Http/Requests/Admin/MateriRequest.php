<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class MateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $batasUkuranKb = (int) config('lms.batas_maksimal_pdf_kb', 20480);
        $materiId = $this->route('materi') ? $this->route('materi')->id : null;

        $validasiHeaderPdf = function (string $attribute, mixed $value, \Closure $fail) {
            if ($value instanceof UploadedFile && $value->isValid()) {
                $realPath = $value->getRealPath();
                if ($realPath && file_exists($realPath)) {
                    $handle = fopen($realPath, 'rb');
                    if ($handle) {
                        $header = fread($handle, 5);
                        fclose($handle);
                        // Jika berkas memiliki isi dan tanda awalnya bukan %PDF-, tolak!
                        if ($header !== '%PDF-') {
                            $fail('Berkas yang diunggah bukan dokumen PDF yang valid (tanda awal berkas tidak sesuai).');
                        }
                    }
                }
            }
        };

        $aturanPdf = ['nullable', 'file', 'mimetypes:application/pdf', "max:{$batasUkuranKb}", $validasiHeaderPdf];
        if ($this->input('tipe') === 'pdf' && ! $materiId) {
            $aturanPdf = ['required', 'file', 'mimetypes:application/pdf', "max:{$batasUkuranKb}", $validasiHeaderPdf];
        }

        $aturanYoutube = ['nullable', 'string', 'url'];
        if ($this->input('tipe') === 'youtube') {
            $aturanYoutube = [
                'required',
                'string',
                'url',
                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            ];
        }

        return [
            'judul' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:pdf,youtube'],
            'berkas_pdf' => $aturanPdf,
            'url_youtube' => $aturanYoutube,
        ];
    }
}
