<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TautanEdukasiRequest;
use App\Models\TautanEdukasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TautanEdukasiController extends Controller
{
    /**
     * Tampilkan daftar tautan edukasi.
     */
    public function index(): View
    {
        $tautan = TautanEdukasi::orderBy('urutan')->orderBy('id')->get();

        return view('admin.tautan-edukasi.index', compact('tautan'));
    }

    /**
     * Formulir tambah tautan edukasi baru.
     */
    public function create(): View
    {
        $urutanBerikutnya = (TautanEdukasi::max('urutan') ?? 0) + 1;

        return view('admin.tautan-edukasi.create', compact('urutanBerikutnya'));
    }

    /**
     * Simpan tautan edukasi baru.
     */
    public function store(TautanEdukasiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif', true);

        TautanEdukasi::create($data);

        return redirect()
            ->route('admin.tautan-edukasi.index')
            ->with('status', 'Tautan edukasi berhasil ditambahkan.');
    }

    /**
     * Formulir edit tautan edukasi.
     */
    public function edit(TautanEdukasi $tautanEdukasi): View
    {
        return view('admin.tautan-edukasi.edit', compact('tautanEdukasi'));
    }

    /**
     * Perbarui tautan edukasi.
     */
    public function update(TautanEdukasiRequest $request, TautanEdukasi $tautanEdukasi): RedirectResponse
    {
        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif', false);

        $tautanEdukasi->update($data);

        return redirect()
            ->route('admin.tautan-edukasi.index')
            ->with('status', 'Tautan edukasi berhasil diperbarui.');
    }

    /**
     * Hapus tautan edukasi.
     */
    public function destroy(TautanEdukasi $tautanEdukasi): RedirectResponse
    {
        $tautanEdukasi->delete();

        return redirect()
            ->route('admin.tautan-edukasi.index')
            ->with('status', 'Tautan edukasi berhasil dihapus.');
    }

    /**
     * Beralih status aktif/non-aktif tautan.
     */
    public function toggle(TautanEdukasi $tautanEdukasi): RedirectResponse
    {
        $tautanEdukasi->update([
            'aktif' => ! $tautanEdukasi->aktif,
        ]);

        $statusTeks = $tautanEdukasi->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.tautan-edukasi.index')
            ->with('status', "Tautan berhasil {$statusTeks}.");
    }
}
