<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SoalRequest;
use App\Models\Kuis;
use App\Models\Soal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SoalController extends Controller
{
    public function index(Kuis $kuis): View
    {
        $daftarSoal = $kuis->soal()->with('pilihanJawaban')->withCount('jawabanSiswa')->orderBy('urutan')->get();

        return view('admin.soal.index', compact('kuis', 'daftarSoal'));
    }

    public function create(Kuis $kuis): View
    {
        return view('admin.soal.create', compact('kuis'));
    }

    public function store(SoalRequest $request, Kuis $kuis): RedirectResponse
    {
        return DB::transaction(function () use ($request, $kuis) {
            $urutanMaks = (int) $kuis->soal()->max('urutan');
            $tipe = $request->validated('tipe_soal');

            $soal = $kuis->soal()->create([
                'tipe_soal' => $tipe,
                'pertanyaan' => $request->validated('pertanyaan'),
                'bobot_poin' => $request->validated('bobot_poin'),
                'urutan' => $urutanMaks + 1,
            ]);

            if ($tipe === 'pilihan_ganda') {
                $pilihan = (array) $request->input('pilihan', []);
                $noUrut = 1;
                foreach ($pilihan as $p) {
                    $teks = trim((string) ($p['teks'] ?? ''));
                    if ($teks !== '') {
                        $soal->pilihanJawaban()->create([
                            'teks_pilihan' => $teks,
                            'apakah_benar' => ! empty($p['apakah_benar']),
                            'urutan' => $noUrut++,
                        ]);
                    }
                }
            } elseif ($tipe === 'isian') {
                $kunciList = (array) $request->input('kunci_isian', []);
                $noUrut = 1;
                foreach ($kunciList as $kunci) {
                    $teks = trim((string) $kunci);
                    if ($teks !== '') {
                        $soal->pilihanJawaban()->create([
                            'teks_pilihan' => $teks,
                            'apakah_benar' => true,
                            'urutan' => $noUrut++,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.kuis.soal.index', $kuis)->with('sukses', 'Butir soal berhasil ditambahkan.');
        });
    }

    public function edit(Kuis $kuis, Soal $soal): View
    {
        $soal->load('pilihanJawaban');
        $punyaJawaban = $soal->jawabanSiswa()->exists();

        return view('admin.soal.edit', compact('kuis', 'soal', 'punyaJawaban'));
    }

    public function update(SoalRequest $request, Kuis $kuis, Soal $soal): RedirectResponse
    {
        // Integritas data penelitian: soal yang sudah punya jawaban siswa tidak boleh diubah tipe, bobot, atau kuncinya
        $punyaJawaban = $soal->jawabanSiswa()->exists();

        if ($punyaJawaban) {
            // Hanya izinkan pembaruan teks pertanyaan dan teks opsi pilihan ganda yang ada
            DB::transaction(function () use ($request, $soal) {
                $soal->update([
                    'pertanyaan' => $request->validated('pertanyaan'),
                ]);

                if ($soal->tipe_soal === 'pilihan_ganda' && $request->has('pilihan_terkunci')) {
                    $pilihanTerkunci = (array) $request->input('pilihan_terkunci', []);
                    foreach ($pilihanTerkunci as $pilihanId => $teksBaru) {
                        $teksBaru = trim((string) $teksBaru);
                        if ($teksBaru !== '') {
                            $soal->pilihanJawaban()->where('id', $pilihanId)->update([
                                'teks_pilihan' => $teksBaru,
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('admin.kuis.soal.index', $kuis)->with('sukses', 'Teks pertanyaan dan pilihan berhasil diperbarui (tipe, bobot, dan status kebenaran kunci tetap terjaga untuk validitas data penelitian).');
        }

        return DB::transaction(function () use ($request, $kuis, $soal) {
            $tipe = $request->validated('tipe_soal');

            $soal->update([
                'tipe_soal' => $tipe,
                'pertanyaan' => $request->validated('pertanyaan'),
                'bobot_poin' => $request->validated('bobot_poin'),
            ]);

            // Hapus pilihan lama
            $soal->pilihanJawaban()->delete();

            if ($tipe === 'pilihan_ganda') {
                $pilihan = (array) $request->input('pilihan', []);
                $noUrut = 1;
                foreach ($pilihan as $p) {
                    $teks = trim((string) ($p['teks'] ?? ''));
                    if ($teks !== '') {
                        $soal->pilihanJawaban()->create([
                            'teks_pilihan' => $teks,
                            'apakah_benar' => ! empty($p['apakah_benar']),
                            'urutan' => $noUrut++,
                        ]);
                    }
                }
            } elseif ($tipe === 'isian') {
                $kunciList = (array) $request->input('kunci_isian', []);
                $noUrut = 1;
                foreach ($kunciList as $kunci) {
                    $teks = trim((string) $kunci);
                    if ($teks !== '') {
                        $soal->pilihanJawaban()->create([
                            'teks_pilihan' => $teks,
                            'apakah_benar' => true,
                            'urutan' => $noUrut++,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.kuis.soal.index', $kuis)->with('sukses', 'Butir soal berhasil diperbarui.');
        });
    }

    public function destroy(Kuis $kuis, Soal $soal): RedirectResponse
    {
        // Integritas data penelitian: Soal yang sudah punya jawaban siswa tidak boleh dihapus
        if ($soal->jawabanSiswa()->exists()) {
            return redirect()->route('admin.kuis.soal.index', $kuis)->with('galat', 'Soal ini tidak dapat dihapus karena sudah memiliki jawaban siswa yang menjadi bagian data penelitian.');
        }

        $urutanDihapus = $soal->urutan;
        $soal->delete();

        // Rapatkan urutan soal
        $kuis->soal()->where('urutan', '>', $urutanDihapus)->decrement('urutan');

        return redirect()->route('admin.kuis.soal.index', $kuis)->with('sukses', 'Butir soal berhasil dihapus.');
    }

    public function naikkanUrutan(Kuis $kuis, Soal $soal): RedirectResponse
    {
        if ($soal->urutan <= 1) {
            return back();
        }

        $soalSebelumnya = $kuis->soal()->where('urutan', $soal->urutan - 1)->first();
        if ($soalSebelumnya) {
            DB::transaction(function () use ($soal, $soalSebelumnya) {
                $targetUrutan = $soal->urutan - 1;
                $urutanAsal = $soal->urutan;

                $soal->update(['urutan' => 0]);
                $soalSebelumnya->update(['urutan' => $urutanAsal]);
                $soal->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan soal berhasil dinaikkan.');
    }

    public function turunkanUrutan(Kuis $kuis, Soal $soal): RedirectResponse
    {
        $urutanMaks = (int) $kuis->soal()->max('urutan');
        if ($soal->urutan >= $urutanMaks) {
            return back();
        }

        $soalBerikutnya = $kuis->soal()->where('urutan', $soal->urutan + 1)->first();
        if ($soalBerikutnya) {
            DB::transaction(function () use ($soal, $soalBerikutnya) {
                $targetUrutan = $soal->urutan + 1;
                $urutanAsal = $soal->urutan;

                $soal->update(['urutan' => 0]);
                $soalBerikutnya->update(['urutan' => $urutanAsal]);
                $soal->update(['urutan' => $targetUrutan]);
            });
        }

        return back()->with('sukses', 'Urutan soal berhasil diturunkan.');
    }
}
