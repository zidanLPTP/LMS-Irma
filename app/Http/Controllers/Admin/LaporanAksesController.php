<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanAksesExport;
use App\Http\Controllers\Controller;
use App\Models\LogAkses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanAksesController extends Controller
{
    /**
     * Tampilkan halaman laporan akses di web.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $tab = $request->input('tab', 'riwayat');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $cari = $request->input('cari');

        // Query ringkasan statistik agregat (hanya 1 query efisien)
        $queryRingkasan = LogAkses::query()
            ->whereHas('user', function ($q) use ($cari) {
                $q->where('peran', 'siswa');
                if ($cari) {
                    $q->where(function ($sub) use ($cari) {
                        $sub->where('nama_lengkap', 'like', "%{$cari}%")
                            ->orWhere('username', 'like', "%{$cari}%");
                    });
                }
            })
            ->when($tanggalMulai, fn ($q) => $q->where('waktu_masuk', '>=', $tanggalMulai.' 00:00:00'))
            ->when($tanggalSelesai, fn ($q) => $q->where('waktu_masuk', '<=', $tanggalSelesai.' 23:59:59'));

        $totalSesi = (clone $queryRingkasan)->count();
        $totalDurasiDetik = (int) (clone $queryRingkasan)->sum('durasi_detik');
        $totalSiswaUnik = (clone $queryRingkasan)->distinct('user_id')->count('user_id');

        if ($tab === 'rekap') {
            $dataRekap = User::query()
                ->where('peran', 'siswa')
                ->when($cari, function ($q) use ($cari) {
                    $q->where(function ($sub) use ($cari) {
                        $sub->where('nama_lengkap', 'like', "%{$cari}%")
                            ->orWhere('username', 'like', "%{$cari}%");
                    });
                })
                ->withCount(['logAkses as jumlah_sesi' => function ($q) use ($tanggalMulai, $tanggalSelesai) {
                    if ($tanggalMulai) {
                        $q->where('waktu_masuk', '>=', $tanggalMulai.' 00:00:00');
                    }
                    if ($tanggalSelesai) {
                        $q->where('waktu_masuk', '<=', $tanggalSelesai.' 23:59:59');
                    }
                }])
                ->withSum(['logAkses as total_durasi' => function ($q) use ($tanggalMulai, $tanggalSelesai) {
                    if ($tanggalMulai) {
                        $q->where('waktu_masuk', '>=', $tanggalMulai.' 00:00:00');
                    }
                    if ($tanggalSelesai) {
                        $q->where('waktu_masuk', '<=', $tanggalSelesai.' 23:59:59');
                    }
                }], 'durasi_detik')
                ->withMin(['logAkses as akses_pertama' => function ($q) use ($tanggalMulai, $tanggalSelesai) {
                    if ($tanggalMulai) {
                        $q->where('waktu_masuk', '>=', $tanggalMulai.' 00:00:00');
                    }
                    if ($tanggalSelesai) {
                        $q->where('waktu_masuk', '<=', $tanggalSelesai.' 23:59:59');
                    }
                }], 'waktu_masuk')
                ->withMax(['logAkses as akses_terakhir' => function ($q) use ($tanggalMulai, $tanggalSelesai) {
                    if ($tanggalMulai) {
                        $q->where('waktu_masuk', '>=', $tanggalMulai.' 00:00:00');
                    }
                    if ($tanggalSelesai) {
                        $q->where('waktu_masuk', '<=', $tanggalSelesai.' 23:59:59');
                    }
                }], 'aktivitas_terakhir')
                ->orderBy('nama_lengkap', 'asc')
                ->paginate(25)
                ->withQueryString();

            return view('admin.laporan.akses', compact(
                'tab',
                'tanggalMulai',
                'tanggalSelesai',
                'cari',
                'totalSesi',
                'totalDurasiDetik',
                'totalSiswaUnik',
                'dataRekap'
            ));
        }

        // Tab Riwayat Akses
        $dataRiwayat = (clone $queryRingkasan)
            ->with('user')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.laporan.akses', compact(
            'tab',
            'tanggalMulai',
            'tanggalSelesai',
            'cari',
            'totalSesi',
            'totalDurasiDetik',
            'totalSiswaUnik',
            'dataRiwayat'
        ));
    }

    /**
     * Ekspor Laporan Akses ke Excel (.xlsx).
     */
    public function ekspor(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $cari = $request->input('cari');

        $namaBerkas = 'laporan-akses-'.Carbon::now('Asia/Jakarta')->format('Ymd-Hi').'.xlsx';

        return Excel::download(new LaporanAksesExport($tanggalMulai, $tanggalSelesai, $cari), $namaBerkas);
    }
}
