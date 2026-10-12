<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanNilaiExport;
use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\Kuis;
use App\Models\User;
use App\Services\PenilaianKuisService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanNilaiController extends Controller
{
    /**
     * Tampilkan halaman laporan nilai di web.
     */
    public function index(Request $request, PenilaianKuisService $penilaianService): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $cari = $request->input('cari');
        $kelasTerpilih = $request->input('kelas');

        // Ambil daftar kelas unik siswa untuk opsi filter
        $daftarKelas = User::where('peran', 'siswa')
            ->whereNotNull('kelas')
            ->distinct()
            ->pluck('kelas');

        // Bab terbit berurutan
        $babTerbit = Bab::where('status_terbit', true)
            ->with(['kuis' => fn ($q) => $q->where('tipe_kuis', 'kuis_bab')])
            ->orderBy('urutan')
            ->get();

        $pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        $posttest = Kuis::where('tipe_kuis', 'posttest')->first();

        // Data Siswa dengan eager loading percobaan kuis selesai
        $dataSiswa = User::query()
            ->where('peran', 'siswa')
            ->when($cari, function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('nama_lengkap', 'like', "%{$cari}%")
                        ->orWhere('username', 'like', "%{$cari}%");
                });
            })
            ->when($kelasTerpilih, fn ($q) => $q->where('kelas', $kelasTerpilih))
            ->with(['percobaanKuis' => function ($q) {
                $q->where('status_pengerjaan', 'selesai')
                    ->where('dibatalkan', false);
            }])
            ->orderBy('nama_lengkap', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.laporan.nilai', compact(
            'cari',
            'kelasTerpilih',
            'daftarKelas',
            'babTerbit',
            'pretest',
            'posttest',
            'dataSiswa',
            'penilaianService'
        ));
    }

    /**
     * Ekspor Laporan Nilai ke Excel (.xlsx).
     */
    public function ekspor(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $cari = $request->input('cari');
        $kelas = $request->input('kelas');

        $namaBerkas = 'laporan-nilai-'.Carbon::now('Asia/Jakarta')->format('Ymd-Hi').'.xlsx';

        return Excel::download(new LaporanNilaiExport($cari, $kelas), $namaBerkas);
    }
}
