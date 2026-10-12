<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengumuman terbit aktif untuk siswa.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $daftarPengumuman = Pengumuman::aktifUntukSiswa()
            ->paginate(10);

        // Ambil id pengumuman yang sudah dibaca oleh user ini
        $dibacaIds = $user->pengumumanDibaca()->pluck('pengumuman_id')->toArray();

        return view('siswa.pengumuman.index', compact('daftarPengumuman', 'dibacaIds'));
    }

    /**
     * Tampilkan detail pengumuman dan catat pembacaan secara idempoten (unik per pasangan).
     */
    public function show(Request $request, Pengumuman $pengumuman): View
    {
        // Pastikan siswa hanya bisa membaca pengumuman terbit dan belum kedaluwarsa
        if ($pengumuman->status !== 'terbit' || ($pengumuman->tanggal_kedaluwarsa && $pengumuman->tanggal_kedaluwarsa->isPast())) {
            abort(404);
        }

        $user = $request->user();

        // Catat pembacaan jika belum pernah dicatat
        if (! $pengumuman->apakahSudahDibaca($user)) {
            $pengumuman->pembaca()->attach($user->id, [
                'dibaca_pada' => now(),
            ]);
        }

        return view('siswa.pengumuman.show', compact('pengumuman'));
    }
}
