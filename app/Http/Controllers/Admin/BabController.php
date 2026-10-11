<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BabRequest;
use App\Models\Bab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BabController extends Controller
{
    public function index(): View
    {
        $daftarBab = Bab::withCount('materi')->with('kuis')->orderBy('urutan')->get();

        return view('admin.bab.index', compact('daftarBab'));
    }

    public function create(): View
    {
        return view('admin.bab.create');
    }

    public function store(BabRequest $request): RedirectResponse
    {
        $urutanMaksimal = (int) Bab::max('urutan');

        Bab::create([
            'judul' => $request->validated('judul'),
            'deskripsi' => $request->validated('deskripsi'),
            'status_terbit' => $request->boolean('status_terbit'),
            'urutan' => $urutanMaksimal + 1,
        ]);

        return redirect()->route('admin.bab.index')->with('sukses', 'Bab baru berhasil ditambahkan.');
    }

    public function edit(Bab $bab): View
    {
        return view('admin.bab.edit', compact('bab'));
    }

    public function update(BabRequest $request, Bab $bab): RedirectResponse
    {
        $bab->update([
            'judul' => $request->validated('judul'),
            'deskripsi' => $request->validated('deskripsi'),
            'status_terbit' => $request->boolean('status_terbit'),
        ]);

        return redirect()->route('admin.bab.index')->with('sukses', 'Data bab berhasil diperbarui.');
    }

    public function destroy(Bab $bab): RedirectResponse
    {
        // Integritas data penelitian: Bab yang kuisnya sudah memiliki percobaan siswa tidak boleh dihapus
        if ($bab->kuis && $bab->kuis->percobaan()->exists()) {
            return redirect()->route('admin.bab.index')->with('galat', 'Bab ini tidak dapat dihapus karena kuisnya sudah memiliki riwayat pengerjaan siswa untuk data penelitian. Silakan ubah status menjadi sembunyikan jika tidak ingin ditampilkan.');
        }

        $urutanDihapus = $bab->urutan;
        $bab->delete();

        // Rapatkan kembali nomor urutan bab setelahnya
        Bab::where('urutan', '>', $urutanDihapus)->decrement('urutan');

        return redirect()->route('admin.bab.index')->with('sukses', 'Bab berhasil dihapus.');
    }

    public function naikkanUrutan(Bab $bab): RedirectResponse
    {
        if ($bab->urutan <= 1) {
            return back();
        }

        $babSebelumnya = Bab::where('urutan', $bab->urutan - 1)->first();
        if ($babSebelumnya) {
            DB::transaction(function () use ($bab, $babSebelumnya) {
                $targetUrutan = $bab->urutan - 1;
                $urutanAsal = $bab->urutan;

                // Gunakan 0 sebagai nilai sementara untuk mencegah bentrok unique constraint
                $bab->update(['urutan' => 0]);
                $babSebelumnya->update(['urutan' => $urutanAsal]);
                $bab->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan bab berhasil dinaikkan.');
    }

    public function turunkanUrutan(Bab $bab): RedirectResponse
    {
        $urutanMaks = (int) Bab::max('urutan');
        if ($bab->urutan >= $urutanMaks) {
            return back();
        }

        $babBerikutnya = Bab::where('urutan', $bab->urutan + 1)->first();
        if ($babBerikutnya) {
            DB::transaction(function () use ($bab, $babBerikutnya) {
                $targetUrutan = $bab->urutan + 1;
                $urutanAsal = $bab->urutan;

                // Gunakan 0 sebagai nilai sementara untuk mencegah bentrok unique constraint
                $bab->update(['urutan' => 0]);
                $babBerikutnya->update(['urutan' => $urutanAsal]);
                $bab->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan bab berhasil diturunkan.');
    }

    public function alihStatusTerbit(Bab $bab): RedirectResponse
    {
        $bab->update(['status_terbit' => ! $bab->status_terbit]);
        $pesan = $bab->status_terbit ? 'Bab berhasil diterbitkan.' : 'Bab berhasil disembunyikan.';

        return back()->with('sukses', $pesan);
    }
}
