<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\TautanEdukasi;
use Illuminate\View\View;

class GameEdukasiController extends Controller
{
    /**
     * Tampilkan halaman daftar game edukasi & tautan penting bagi siswa.
     */
    public function index(): View
    {
        $tautan = TautanEdukasi::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        return view('siswa.game-edukasi.index', compact('tautan'));
    }
}
