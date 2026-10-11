<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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

        $aturanPdf = ['nullable', 'file', 'mimetypes:application/pdf', "max:{$batasUkuranKb}"];
        if ($this->input('tipe') === 'pdf' && ! $materiId) {
            $aturanPdf = ['required', 'file', 'mimetypes:application/pdf', "max:{$batasUkuranKb}"];
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
