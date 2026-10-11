<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\Soal;
use App\Services\BukaAksesService;
use App\Services\PenilaianKuisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KuisController extends Controller
{
    public function __construct(
        protected PenilaianKuisService $penilaianService,
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * 1. GET kuis/{kuis}: Halaman pengantar kuis.
     */
    public function show(Kuis $kuis): View|RedirectResponse
    {
        $user = auth()->user();

        // Pemeriksaan keterbukaan kuis
        $terbuka = $this->bukaAksesService->apakahKuisTerbuka($kuis, $user);
        $bisaMulai = $this->penilaianService->bisaMulaiPercobaan($kuis, $user);

        // Cari apakah ada percobaan menggantung yang sedang dikerjakan
        $percobaanAktif = PercobaanKuis::where('kuis_id', $kuis->id)
            ->where('user_id', $user->id)
            ->where('dibatalkan', false)
            ->where('status_pengerjaan', 'sedang_mengerjakan')
            ->first();

        // Riwayat percobaan kuis siswa
        $riwayatPercobaan = PercobaanKuis::where('kuis_id', $kuis->id)
            ->where('user_id', $user->id)
            ->where('dibatalkan', false)
            ->orderBy('nomor_percobaan')
            ->get();

        $kesempatanTerpakai = $riwayatPercobaan->count();
        $maksimalKesempatan = $kuis->maksimalKesempatanEfektif();
        $kesempatanSisa = max(0, $maksimalKesempatan - $kesempatanTerpakai);

        $totalSoal = $kuis->soal()->count();
        $adaSoalOtomatis = $kuis->soal()->whereIn('tipe_soal', ['pilihan_ganda', 'isian'])->exists();
        $batasWaktuPerSoal = $adaSoalOtomatis ? $kuis->batasWaktuEfektif() : null;
        $nilaiLulus = ($kuis->tipe_kuis === 'kuis_bab' && $adaSoalOtomatis) ? $kuis->nilaiLulusEfektif() : null;

        return view('siswa.kuis.pengantar', compact(
            'kuis',
            'terbuka',
            'bisaMulai',
            'percobaanAktif',
            'riwayatPercobaan',
            'kesempatanTerpakai',
            'kesempatanSisa',
            'maksimalKesempatan',
            'totalSoal',
            'batasWaktuPerSoal',
            'nilaiLulus'
        ));
    }

    /**
     * 2. POST kuis/{kuis}/mulai: Mulai atau lanjutkan percobaan kuis.
     */
    public function mulai(Kuis $kuis): RedirectResponse
    {
        $user = auth()->user();

        if (! $this->bukaAksesService->apakahKuisTerbuka($kuis, $user)) {
            return redirect()->route('siswa.kuis.show', $kuis)
                ->with('galat', 'Kuis ini masih terkunci. Silakan selesaikan tahapan belajar sebelumnya terlebih dahulu.');
        }

        try {
            $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $user);
        } catch (\DomainException $e) {
            return redirect()->route('siswa.kuis.show', $kuis)->with('galat', $e->getMessage());
        }

        return redirect()->route('siswa.percobaan.soal', $percobaan);
    }

    /**
     * 3. GET percobaan/{percobaan}/soal: Tampilkan SATU butir soal aktif per layar.
     */
    public function soal(PercobaanKuis $percobaan): View|RedirectResponse
    {
        Gate::authorize('view', $percobaan);

        // Jika percobaan sudah selesai, langsung arahkan ke hasil
        if ($percobaan->status_pengerjaan === 'selesai' || $percobaan->dibatalkan) {
            return redirect()->route('siswa.percobaan.hasil', $percobaan);
        }

        // 1. Finalkan soal yang kadaluarsa terlebih dahulu
        $this->penilaianService->finalkanSoalOtomatisKadaluarsa($percobaan);

        $urutanSoal = $percobaan->urutan_soal_teracak ?? [];
        $totalSoal = count($urutanSoal);

        // 2. Cari soal aktif pertama di urutan yang belum memiliki waktu_jawab
        $soalAktifId = null;
        $nomorSoalAktif = 1;

        foreach ($urutanSoal as $idx => $sId) {
            $sudahJawab = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                ->where('soal_id', $sId)
                ->whereNotNull('waktu_jawab')
                ->exists();

            if (! $sudahJawab) {
                $soalAktifId = $sId;
                $nomorSoalAktif = $idx + 1;
                break;
            }
        }

        // Jika semua soal sudah dijawab, serahkan otomatis
        if (! $soalAktifId) {
            $this->penilaianService->serahkanPercobaan($percobaan);

            return redirect()->route('siswa.percobaan.hasil', $percobaan);
        }

        $soalAktif = Soal::with('pilihanJawaban')->findOrFail($soalAktifId);

        // 3. Mulai soal (waktu_mulai_soal dicatat server sekali, refresh tidak mengulang)
        $jawaban = $this->penilaianService->mulaiSoal($percobaan, $soalAktif);

        // 4. Hitung sisa detik di SERVER
        $sisaDetik = null;
        if ($soalAktif->dinilaiOtomatis()) {
            $batasWaktu = $percobaan->kuis->batasWaktuEfektif();
            $waktuMulai = $jawaban->waktu_mulai_soal;
            $detikBerjalan = (int) $waktuMulai->diffInSeconds(now());
            $sisaDetik = max(0, $batasWaktu - $detikBerjalan);
        }

        // Opsi pilihan untuk PG (sembunyikan atribut kunci/apakah_benar)
        $pilihanOpsi = $soalAktif->pilihanJawaban->shuffle();

        return view('siswa.kuis.soal', compact(
            'percobaan',
            'soalAktif',
            'nomorSoalAktif',
            'totalSoal',
            'jawaban',
            'sisaDetik',
            'pilihanOpsi'
        ));
    }

    /**
     * 4. POST percobaan/{percobaan}/soal/{soal}/jawab: Simpan jawaban butir soal.
     */
    public function jawab(Request $request, PercobaanKuis $percobaan, Soal $soal): RedirectResponse
    {
        Gate::authorize('kerjakan', $percobaan);

        $urutanSoal = $percobaan->urutan_soal_teracak ?? [];

        // Validasi bahwa soal yang dijawab adalah soal aktif yang sah
        $soalAktifId = null;
        foreach ($urutanSoal as $sId) {
            $sudahJawab = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                ->where('soal_id', $sId)
                ->whereNotNull('waktu_jawab')
                ->exists();

            if (! $sudahJawab) {
                $soalAktifId = $sId;
                break;
            }
        }

        if ((int) $soalAktifId !== (int) $soal->id) {
            abort(403, 'Anda hanya dapat menjawab soal yang sedang aktif.');
        }

        $dataJawaban = [
            'pilihan_jawaban_id' => $request->input('pilihan_jawaban_id'),
            'jawaban_teks' => $request->input('jawaban_teks'),
        ];

        try {
            $this->penilaianService->simpanJawaban($percobaan, $soal, $dataJawaban);
        } catch (\DomainException $e) {
            // Jika sudah dijawab atau kadaluarsa, tetap lanjutkan ke soal berikutnya
        }

        // Periksa apakah ini soal terakhir di urutan
        $indeksSoalIni = array_search($soal->id, $urutanSoal, true);
        if ($indeksSoalIni === count($urutanSoal) - 1) {
            // Soal terakhir -> serahkan percobaan
            $this->penilaianService->serahkanPercobaan($percobaan);

            return redirect()->route('siswa.percobaan.hasil', $percobaan);
        }

        return redirect()->route('siswa.percobaan.soal', $percobaan);
    }

    /**
     * 5. POST percobaan/{percobaan}/serahkan: Serahkan kuis secara manual.
     */
    public function serahkan(PercobaanKuis $percobaan): RedirectResponse
    {
        Gate::authorize('view', $percobaan);

        $this->penilaianService->serahkanPercobaan($percobaan);

        return redirect()->route('siswa.percobaan.hasil', $percobaan);
    }

    /**
     * 6. GET percobaan/{percobaan}/hasil: Halaman hasil kuis yang selesai.
     */
    public function hasil(PercobaanKuis $percobaan): View|RedirectResponse
    {
        Gate::authorize('view', $percobaan);

        if ($percobaan->status_pengerjaan !== 'selesai') {
            return redirect()->route('siswa.percobaan.soal', $percobaan);
        }

        $kuis = $percobaan->kuis;
        $user = auth()->user();

        $maksimalKesempatan = $kuis->maksimalKesempatanEfektif();
        $kesempatanTerpakai = PercobaanKuis::where('kuis_id', $kuis->id)
            ->where('user_id', $user->id)
            ->where('dibatalkan', false)
            ->count();
        $kesempatanSisa = max(0, $maksimalKesempatan - $kesempatanTerpakai);

        $bisaCobaLagi = ($percobaan->status_kelulusan === 'belum_lulus') && ($kesempatanSisa > 0);

        // Langkah belajar berikutnya bila bab selesai
        $bab = $kuis->bab;
        $babBerikutnya = null;
        if ($bab && $this->bukaAksesService->apakahBabSelesai($bab, $user)) {
            $babBerikutnya = Bab::where('status_terbit', true)
                ->where('urutan', '>', $bab->urutan)
                ->orderBy('urutan')
                ->first();
        }

        return view('siswa.kuis.hasil', compact(
            'percobaan',
            'kuis',
            'maksimalKesempatan',
            'kesempatanTerpakai',
            'kesempatanSisa',
            'bisaCobaLagi',
            'babBerikutnya'
        ));
    }
}
