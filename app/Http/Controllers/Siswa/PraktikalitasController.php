<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\InstrumenPraktikalitas;
use App\Models\ResponPraktikalitas;
use App\Models\SaranPraktikalitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PraktikalitasController extends Controller
{
    /**
     * Tampilkan formulir atau hasil pengisian praktikalitas oleh siswa.
     */
    public function index(): View
    {
        $userId = Auth::id();
        $sudahMengisi = ResponPraktikalitas::where('user_id', $userId)->exists();

        if ($sudahMengisi) {
            $responList = ResponPraktikalitas::with('instrumen')
                ->where('user_id', $userId)
                ->get()
                ->sortBy(fn ($r) => $r->instrumen->urutan ?? 0);

            $saran = SaranPraktikalitas::where('user_id', $userId)->first();

            return view('siswa.praktikalitas.index', [
                'sudahMengisi' => true,
                'responList' => $responList,
                'saran' => $saran,
            ]);
        }

        $instrumen = InstrumenPraktikalitas::orderBy('urutan')->get();

        return view('siswa.praktikalitas.index', [
            'sudahMengisi' => false,
            'instrumen' => $instrumen,
        ]);
    }

    /**
     * Simpan respon lembar praktikalitas dari siswa.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        // Cegah pengisian ganda
        if (ResponPraktikalitas::where('user_id', $userId)->exists()) {
            return redirect()
                ->route('siswa.praktikalitas.index')
                ->with('status', 'Anda sudah pernah mengisi lembar praktikalitas ini.');
        }

        $instrumenList = InstrumenPraktikalitas::all();

        $rules = [
            'skor' => ['required', 'array'],
            'saran' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($instrumenList as $item) {
            $rules["skor.{$item->id}"] = ['required', 'integer', 'in:1,2,3,4'];
        }

        $messages = [
            'skor.*.required' => 'Mohon berikan penilaian untuk semua pernyataan di bawah ini.',
            'skor.*.in' => 'Skala penilaian harus antara 1 sampai 4.',
        ];

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($userId, $validated, $request) {
            foreach ($validated['skor'] as $instrumenId => $nilaiSkor) {
                ResponPraktikalitas::create([
                    'user_id' => $userId,
                    'instrumen_id' => $instrumenId,
                    'skor' => (int) $nilaiSkor,
                ]);
            }

            if (! empty($validated['saran'])) {
                SaranPraktikalitas::create([
                    'user_id' => $userId,
                    'saran' => trim($validated['saran']),
                ]);
            }
        });

        return redirect()
            ->route('siswa.praktikalitas.index')
            ->with('status', 'Terima kasih! Respon dan penilaian kepraktisan Anda telah berhasil disimpan.');
    }
}
