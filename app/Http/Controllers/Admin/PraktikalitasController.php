<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PraktikalitasExport;
use App\Http\Controllers\Controller;
use App\Models\InstrumenPraktikalitas;
use App\Models\ResponPraktikalitas;
use App\Models\SaranPraktikalitas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PraktikalitasController extends Controller
{
    /**
     * Tampilkan rekapitulasi penilaian praktikalitas media.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $totalResponden = ResponPraktikalitas::distinct()->count('user_id');
        $instrumenList = InstrumenPraktikalitas::with('respon')->orderBy('urutan')->get();
        $jumlahButir = $instrumenList->count();

        $totalSkorSemua = 0;
        $totalMaksSemua = 0;

        $rekapButir = $instrumenList->map(function ($item) use (&$totalSkorSemua, &$totalMaksSemua) {
            $respon = $item->respon;
            $n = $respon->count();
            $s4 = $respon->where('skor', 4)->count();
            $s3 = $respon->where('skor', 3)->count();
            $s2 = $respon->where('skor', 2)->count();
            $s1 = $respon->where('skor', 1)->count();

            $totalSkor = $respon->sum('skor');
            $skorMaks = $n * 4;
            $persen = $skorMaks > 0 ? round(($totalSkor / $skorMaks) * 100, 2) : 0;

            $totalSkorSemua += $totalSkor;
            $totalMaksSemua += $skorMaks;

            $kategori = match (true) {
                $persen >= 85 => 'Sangat Praktis',
                $persen >= 70 => 'Praktis',
                $persen >= 50 => 'Cukup Praktis',
                default => 'Kurang Praktis',
            };

            return [
                'id' => $item->id,
                'urutan' => $item->urutan,
                'aspek' => $item->aspek,
                'pernyataan' => $item->pernyataan,
                'n' => $n,
                's4' => $s4,
                's3' => $s3,
                's2' => $s2,
                's1' => $s1,
                'total_skor' => $totalSkor,
                'skor_maks' => $skorMaks,
                'persen' => $persen,
                'kategori' => $kategori,
            ];
        });

        $persenTotal = $totalMaksSemua > 0 ? round(($totalSkorSemua / $totalMaksSemua) * 100, 2) : 0;
        $kategoriTotal = match (true) {
            $persenTotal >= 85 => 'Sangat Praktis',
            $persenTotal >= 70 => 'Praktis',
            $persenTotal >= 50 => 'Cukup Praktis',
            default => 'Kurang Praktis',
        };

        $saranList = SaranPraktikalitas::with('user')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.praktikalitas.index', compact(
            'totalResponden',
            'jumlahButir',
            'rekapButir',
            'totalSkorSemua',
            'totalMaksSemua',
            'persenTotal',
            'kategoriTotal',
            'saranList'
        ));
    }

    /**
     * Unduh laporan praktikalitas media dalam format Excel.
     */
    public function ekspor(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $namaBerkas = 'laporan-praktikalitas-biostem-' . Carbon::now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new PraktikalitasExport, $namaBerkas);
    }
}
