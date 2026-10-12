<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    /**
     * Tampilkan daftar notifikasi milik siswa login.
     */
    public function index(Request $request): View
    {
        $daftarNotifikasi = $request->user()
            ->notifikasi()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('siswa.notifikasi.index', compact('daftarNotifikasi'));
    }

    /**
     * Buka satu notifikasi, tandai terbaca, dan alihkan ke tautan tujuan.
     */
    public function buka(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        // Kebijakan keamanan: siswa hanya bisa membuka notifikasi miliknya sendiri
        if ((int) $notifikasi->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $notifikasi->tandaiDibaca();

        if ($notifikasi->tautan) {
            return redirect($notifikasi->tautan);
        }

        return redirect()->route('siswa.notifikasi.index');
    }

    /**
     * Tandai semua notifikasi siswa sebagai telah dibaca.
     */
    public function tandaiSemuaDibaca(Request $request): RedirectResponse
    {
        $request->user()
            ->notifikasi()
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        return redirect()->route('siswa.notifikasi.index')
            ->with('sukses', __('notifikasi.pesan.semua_ditandai_dibaca'));
    }
}
