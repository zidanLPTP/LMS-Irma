<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SimpanPengumumanRequest;
use App\Models\Pengumuman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    /**
     * Tampilkan daftar pengumuman.
     */
    public function index(Request $request): View
    {
        $daftarPengumuman = Pengumuman::with('admin')
            ->orderBy('disematkan', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.pengumuman.index', compact('daftarPengumuman'));
    }

    /**
     * Form tambah pengumuman baru.
     */
    public function create(): View
    {
        return view('admin.pengumuman.create');
    }

    /**
     * Simpan pengumuman baru.
     */
    public function store(SimpanPengumumanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Pengumuman::create([
            'admin_id' => $request->user()->id,
            'judul' => $validated['judul'],
            'isi' => strip_tags($validated['isi']), // Pastikan teks biasa tanpa HTML
            'status' => $validated['status'],
            'disematkan' => $request->boolean('disematkan'),
            'tanggal_kedaluwarsa' => $validated['tanggal_kedaluwarsa'] ?? null,
        ]);

        return redirect()->route('admin.pengumuman.index')
            ->with('sukses', __('pengumuman.pesan.berhasil_dibuat'));
    }

    /**
     * Form ubah pengumuman.
     */
    public function edit(Pengumuman $pengumuman): View
    {
        return view('admin.pengumuman.edit', compact('pengumuman'));
    }

    /**
     * Perbarui pengumuman.
     */
    public function update(SimpanPengumumanRequest $request, Pengumuman $pengumuman): RedirectResponse
    {
        $validated = $request->validated();

        $pengumuman->update([
            'judul' => $validated['judul'],
            'isi' => strip_tags($validated['isi']), // Pastikan teks biasa tanpa HTML
            'status' => $validated['status'],
            'disematkan' => $request->boolean('disematkan'),
            'tanggal_kedaluwarsa' => $validated['tanggal_kedaluwarsa'] ?? null,
        ]);

        return redirect()->route('admin.pengumuman.index')
            ->with('sukses', __('pengumuman.pesan.berhasil_diperbarui'));
    }

    /**
     * Hapus pengumuman.
     */
    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->delete();

        return redirect()->route('admin.pengumuman.index')
            ->with('sukses', __('pengumuman.pesan.berhasil_dihapus'));
    }
}
