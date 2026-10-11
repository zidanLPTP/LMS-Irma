<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\PelacakAksesService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DetakJantungController extends Controller
{
    /**
     * Rekam sinyal detak jantung browser (heartbeat).
     * Hanya mencatat siswa (admin mendapat 204 tanpa mencatat).
     * Abaikan seluruh isi body (server menghitung waktu sendiri).
     */
    public function __invoke(Request $request, PelacakAksesService $pelacakAkses): Response
    {
        $user = $request->user();

        if ($user && $user->isSiswa()) {
            $pelacakAkses->rekamDetakJantung($user);
        }

        return response()->noContent();
    }
}
