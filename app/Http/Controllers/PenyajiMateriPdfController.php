<?php

namespace App\Http\Controllers\MateriPdfController;

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Services\BukaAksesService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenyajiMateriPdfController extends Controller
{
    public function __construct(
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * Sajikan berkas PDF privat dari storage/app/materi/
     * Hanya boleh diakses oleh siswa berhak (bab terbuka & terbit) atau admin.
     */
    public function __invoke(Materi $materi): BinaryFileResponse|Response
    {
        $user = auth()->user();

        // 1. Validasi tipe materi
        if ($materi->tipe !== 'pdf' || empty($materi->berkas_pdf)) {
            abort(404, 'Materi PDF tidak ditemukan.');
        }

        // 2. Pemeriksaan hak akses
        if (! $user->isAdmin()) {
            $bab = $materi->bab;
            if (! $bab || ! $bab->status_terbit) {
                abort(403, 'Akses ditolak: Bab belum diterbitkan.');
            }

            if (! $this->bukaAksesService->apakahBabTerbuka($bab, $user)) {
                abort(403, 'Akses ditolak: Bab ini masih terkunci.');
            }
        }

        // 3. Pastikan berkas ada di storage privat local
        if (! Storage::disk('local')->exists($materi->berkas_pdf)) {
            abort(404, 'Berkas fisik PDF tidak ditemukan pada server.');
        }

        $pathAsli = Storage::disk('local')->path($materi->berkas_pdf);

        // Sajikan berkas secara inline dengan Content-Type application/pdf dan Cache-Control private
        $response = response()->file($pathAsli, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="materi_'.$materi->id.'.pdf"',
        ]);

        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
