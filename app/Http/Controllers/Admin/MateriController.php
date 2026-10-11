<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MateriRequest;
use App\Models\Bab;
use App\Models\Materi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function index(Bab $bab): View
    {
        $daftarMateri = $bab->materi()->orderBy('urutan')->get();

        return view('admin.materi.index', compact('bab', 'daftarMateri'));
    }

    public function create(Bab $bab): View
    {
        return view('admin.materi.create', compact('bab'));
    }

    public function store(MateriRequest $request, Bab $bab): RedirectResponse
    {
        $tipe = $request->validated('tipe');
        $berkasPdfPath = null;
        $urlYoutube = null;

        if ($tipe === 'pdf') {
            $berkas = $request->file('berkas_pdf');
            $namaUnik = 'materi_'.Str::random(20).'.pdf';
            // Simpan di disk lokal privat storage/app/materi/ (bukan public)
            $berkasPdfPath = $berkas->storeAs('materi', $namaUnik, 'local');
        } elseif ($tipe === 'youtube') {
            $urlYoutube = $this->bersihkanUrlYoutube($request->validated('url_youtube'));
        }

        $urutanMaksimal = (int) $bab->materi()->max('urutan');

        $bab->materi()->create([
            'judul' => $request->validated('judul'),
            'tipe' => $tipe,
            'berkas_pdf' => $berkasPdfPath,
            'url_youtube' => $urlYoutube,
            'urutan' => $urutanMaksimal + 1,
        ]);

        return redirect()->route('admin.bab.materi.index', $bab)->with('sukses', 'Materi berhasil ditambahkan.');
    }

    public function edit(Bab $bab, Materi $materi): View
    {
        return view('admin.materi.edit', compact('bab', 'materi'));
    }

    public function update(MateriRequest $request, Bab $bab, Materi $materi): RedirectResponse
    {
        $tipe = $request->validated('tipe');

        $dataPembaruan = [
            'judul' => $request->validated('judul'),
            'tipe' => $tipe,
        ];

        if ($tipe === 'pdf') {
            if ($request->hasFile('berkas_pdf')) {
                // Hapus berkas lama jika ada
                if ($materi->berkas_pdf && Storage::disk('local')->exists($materi->berkas_pdf)) {
                    Storage::disk('local')->delete($materi->berkas_pdf);
                }
                $berkas = $request->file('berkas_pdf');
                $namaUnik = 'materi_'.Str::random(20).'.pdf';
                $dataPembaruan['berkas_pdf'] = $berkas->storeAs('materi', $namaUnik, 'local');
            }
            $dataPembaruan['url_youtube'] = null;
        } elseif ($tipe === 'youtube') {
            $dataPembaruan['url_youtube'] = $this->bersihkanUrlYoutube($request->validated('url_youtube'));
            if ($materi->berkas_pdf && Storage::disk('local')->exists($materi->berkas_pdf)) {
                Storage::disk('local')->delete($materi->berkas_pdf);
            }
            $dataPembaruan['berkas_pdf'] = null;
        }

        $materi->update($dataPembaruan);

        return redirect()->route('admin.bab.materi.index', $bab)->with('sukses', 'Materi berhasil diperbarui.');
    }

    public function destroy(Bab $bab, Materi $materi): RedirectResponse
    {
        if ($materi->berkas_pdf && Storage::disk('local')->exists($materi->berkas_pdf)) {
            Storage::disk('local')->delete($materi->berkas_pdf);
        }

        $urutanDihapus = $materi->urutan;
        $materi->delete();

        // Rapatkan kembali nomor urutan materi bab ini
        $bab->materi()->where('urutan', '>', $urutanDihapus)->decrement('urutan');

        return redirect()->route('admin.bab.materi.index', $bab)->with('sukses', 'Materi berhasil dihapus.');
    }

    public function naikkanUrutan(Bab $bab, Materi $materi): RedirectResponse
    {
        if ($materi->urutan <= 1) {
            return back();
        }

        $materiSebelumnya = $bab->materi()->where('urutan', $materi->urutan - 1)->first();
        if ($materiSebelumnya) {
            DB::transaction(function () use ($materi, $materiSebelumnya) {
                $targetUrutan = $materi->urutan - 1;
                $urutanAsal = $materi->urutan;

                $materi->update(['urutan' => 0]);
                $materiSebelumnya->update(['urutan' => $urutanAsal]);
                $materi->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan materi berhasil dinaikkan.');
    }

    public function turunkanUrutan(Bab $bab, Materi $materi): RedirectResponse
    {
        $urutanMaks = (int) $bab->materi()->max('urutan');
        if ($materi->urutan >= $urutanMaks) {
            return back();
        }

        $materiBerikutnya = $bab->materi()->where('urutan', $materi->urutan + 1)->first();
        if ($materiBerikutnya) {
            DB::transaction(function () use ($materi, $materiBerikutnya) {
                $targetUrutan = $materi->urutan + 1;
                $urutanAsal = $materi->urutan;

                $materi->update(['urutan' => 0]);
                $materiBerikutnya->update(['urutan' => $urutanAsal]);
                $materi->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan materi berhasil diturunkan.');
    }

    /**
     * Ekstrak YouTube Video ID dan format ke youtube-nocookie.com embed URL.
     */
    protected function bersihkanUrlYoutube(string $url): string
    {
        preg_match('/(youtube\.com\/(watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches);
        $videoId = $matches[3] ?? '';

        if (! empty($videoId)) {
            return "https://www.youtube-nocookie.com/embed/{$videoId}";
        }

        return $url;
    }
}
