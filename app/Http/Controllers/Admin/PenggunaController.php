<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetKesempatanRequest;
use App\Http\Requests\Admin\SimpanPenggunaRequest;
use App\Http\Requests\Admin\UbahPenggunaRequest;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\RiwayatTindakanAdmin;
use App\Models\User;
use App\Services\BukaAksesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function __construct(
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * 1. Daftar Siswa dengan pencarian, filter, dan agregasi bebas N+1.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $cari = $request->input('cari');
        $kelasDipilih = $request->input('kelas');
        $statusDipilih = $request->input('status');

        $query = User::query()
            ->where('peran', 'siswa')
            ->when($cari, function ($q, $cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('nama_lengkap', 'like', "%{$cari}%")
                        ->orWhere('username', 'like', "%{$cari}%")
                        ->orWhere('email', 'like', "%{$cari}%");
                });
            })
            ->when($kelasDipilih, fn ($q, $k) => $q->where('kelas', $k))
            ->when($statusDipilih !== null && $statusDipilih !== '', function ($q) use ($statusDipilih) {
                if ($statusDipilih === 'aktif') {
                    $q->where('aktif', true);
                } elseif ($statusDipilih === 'nonaktif') {
                    $q->where('aktif', false);
                }
            })
            ->withCount('logAkses as jumlah_sesi')
            ->withMax('logAkses as akses_terakhir', 'aktivitas_terakhir')
            ->orderBy('nama_lengkap', 'asc');

        $daftarSiswa = $query->paginate(25)->withQueryString();

        // Hitung progres singkat untuk tiap siswa di halaman aktif
        $daftarSiswa->getCollection()->transform(function ($siswa) {
            $langkah = $this->bukaAksesService->daftarLangkahBelajar($siswa);
            $totalLangkah = count($langkah);
            $selesaiLangkah = count(array_filter($langkah, fn ($l) => $l['status'] === 'selesai'));

            $siswa->total_langkah = $totalLangkah;
            $siswa->selesai_langkah = $selesaiLangkah;

            return $siswa;
        });

        $daftarKelas = User::where('peran', 'siswa')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        return view('admin.pengguna.index', compact(
            'daftarSiswa',
            'daftarKelas',
            'cari',
            'kelasDipilih',
            'statusDipilih'
        ));
    }

    /**
     * Form Tambah Siswa Manual.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.pengguna.create');
    }

    /**
     * Simpan Siswa Baru.
     */
    public function store(SimpanPenggunaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $kataSandiSementara = $this->buatKataSandiAcak();

        $siswa = User::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'kelas' => $validated['kelas'] ?? null,
            'password' => Hash::make($kataSandiSementara),
            'peran' => 'siswa',
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => $siswa->id,
            'jenis' => 'ubah_data',
            'keterangan' => 'Menambahkan akun siswa baru secara manual.',
        ]);

        return redirect()->route('admin.pengguna.index')
            ->with('sukses', __('pengguna.pesan.berhasil_tambah'))
            ->with('sandi_sementara_baru', [
                'nama' => $siswa->nama_lengkap,
                'username' => $siswa->username,
                'sandi' => $kataSandiSementara,
            ]);
    }

    /**
     * Detail Siswa.
     */
    public function show(User $pengguna): View
    {
        Gate::authorize('view', $pengguna);

        $langkahBelajar = $this->bukaAksesService->daftarLangkahBelajar($pengguna);

        $riwayatPercobaan = PercobaanKuis::with(['kuis', 'dibatalkanOleh'])
            ->where('user_id', $pengguna->id)
            ->orderBy('id', 'desc')
            ->get();

        $sesiAkses = $pengguna->logAkses()
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $jejakAudit = $pengguna->riwayatTindakan()
            ->with('admin')
            ->orderBy('id', 'desc')
            ->get();

        $daftarKuis = Kuis::orderBy('id')->get();

        return view('admin.pengguna.show', compact(
            'pengguna',
            'langkahBelajar',
            'riwayatPercobaan',
            'sesiAkses',
            'jejakAudit',
            'daftarKuis'
        ));
    }

    /**
     * Form Edit Data Siswa.
     */
    public function edit(User $pengguna): View
    {
        Gate::authorize('update', $pengguna);

        return view('admin.pengguna.edit', compact('pengguna'));
    }

    /**
     * Perbarui Data Siswa.
     */
    public function update(UbahPenggunaRequest $request, User $pengguna): RedirectResponse
    {
        Gate::authorize('update', $pengguna);

        $validated = $request->validated();

        $pengguna->update([
            'nama_lengkap' => $validated['nama_lengkap'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'kelas' => $validated['kelas'] ?? null,
        ]);

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => $pengguna->id,
            'jenis' => 'ubah_data',
            'keterangan' => 'Memperbarui data identitas profil siswa.',
        ]);

        return redirect()->route('admin.pengguna.show', $pengguna)
            ->with('sukses', __('pengguna.pesan.berhasil_ubah'));
    }

    /**
     * Nonaktifkan atau Aktifkan Akun Siswa.
     */
    public function toggleAktif(Request $request, User $pengguna): RedirectResponse
    {
        Gate::authorize('toggleAktif', $pengguna);

        $statusSebelumnya = $pengguna->aktif;
        $pengguna->update(['aktif' => ! $statusSebelumnya]);

        $jenis = $statusSebelumnya ? 'nonaktifkan' : 'aktifkan';
        $keterangan = $statusSebelumnya
            ? 'Menonaktifkan akses akun siswa.'
            : 'Mengaktifkan kembali akses akun siswa.';

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => $pengguna->id,
            'jenis' => $jenis,
            'keterangan' => $keterangan,
        ]);

        $pesan = $statusSebelumnya
            ? __('pengguna.pesan.berhasil_nonaktif')
            : __('pengguna.pesan.berhasil_aktif');

        return back()->with('sukses', $pesan);
    }

    /**
     * Reset Password Siswa oleh Admin.
     */
    public function resetPassword(Request $request, User $pengguna): RedirectResponse
    {
        Gate::authorize('resetPassword', $pengguna);

        $sandiSementara = $this->buatKataSandiAcak();

        $pengguna->update([
            'password' => Hash::make($sandiSementara),
            'wajib_ganti_password' => true,
        ]);

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => $pengguna->id,
            'jenis' => 'reset_password',
            'keterangan' => 'Mereset kata sandi siswa ke kata sandi sementara acak.',
        ]);

        return redirect()->route('admin.pengguna.show', $pengguna)
            ->with('sukses', __('pengguna.pesan.berhasil_reset_password'))
            ->with('sandi_sementara', $sandiSementara);
    }

    /**
     * Reset Kesempatan Kuis Siswa oleh Admin.
     */
    public function resetKesempatan(ResetKesempatanRequest $request, User $pengguna): RedirectResponse
    {
        Gate::authorize('update', $pengguna);

        $validated = $request->validated();
        $kuisId = $validated['kuis_id'];
        $alasan = $validated['alasan'];

        // Tandai semua percobaan kuis yang belum dibatalkan menjadi dibatalkan
        $jumlahDibatalkan = PercobaanKuis::where('kuis_id', $kuisId)
            ->where('user_id', $pengguna->id)
            ->where('dibatalkan', false)
            ->update([
                'dibatalkan' => true,
                'dibatalkan_oleh' => $request->user()->id,
                'dibatalkan_pada' => now(),
                'alasan_pembatalan' => $alasan,
            ]);

        $kuis = Kuis::find($kuisId);

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => $pengguna->id,
            'jenis' => 'reset_kesempatan',
            'keterangan' => "Mereset kesempatan kuis '{$kuis?->judul}'. {$jumlahDibatalkan} percobaan ditandai dibatalkan. Alasan: {$alasan}",
        ]);

        return redirect()->route('admin.pengguna.show', $pengguna)
            ->with('sukses', __('pengguna.pesan.berhasil_reset_kesempatan'));
    }

    /**
     * Halaman Pemeriksaan Akun Ganda.
     */
    public function ganda(): View
    {
        Gate::authorize('viewAny', User::class);

        // Ambil nama lengkap yang duplikat (case-insensitive)
        $namaDuplikat = User::select(DB::raw('LOWER(nama_lengkap) as nama_lower'))
            ->where('peran', 'siswa')
            ->groupBy('nama_lower')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nama_lower');

        $grupAkunGanda = collect();

        if ($namaDuplikat->isNotEmpty()) {
            $akunList = User::where('peran', 'siswa')
                ->whereIn(DB::raw('LOWER(nama_lengkap)'), $namaDuplikat)
                ->withCount(['percobaanKuis', 'progresMateri', 'logAkses'])
                ->orderBy('nama_lengkap')
                ->get();

            $grupAkunGanda = $akunList->groupBy(fn ($u) => strtolower($u->nama_lengkap));
        }

        return view('admin.pengguna.ganda', compact('grupAkunGanda'));
    }

    /**
     * Hapus Akun Tanpa Data.
     */
    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        Gate::authorize('delete', $pengguna);

        $nama = $pengguna->nama_lengkap;
        $username = $pengguna->username;

        RiwayatTindakanAdmin::create([
            'admin_id' => $request->user()->id,
            'siswa_id' => null, // Karena akun siswa akan dihapus
            'jenis' => 'hapus',
            'keterangan' => "Menghapus akun kosong siswa: {$nama} ({$username}).",
        ]);

        $pengguna->delete();

        return back()->with('sukses', __('pengguna.pesan.berhasil_hapus'));
    }

    /**
     * Helper membuat kata sandi acak 10 karakter yang ramah baca (tanpa karakter ambigu).
     */
    protected function buatKataSandiAcak(): string
    {
        $karakter = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $panjangKarakter = strlen($karakter);
        $sandi = '';

        for ($i = 0; $i < 10; $i++) {
            $sandi .= $karakter[random_int(0, $panjangKarakter - 1)];
        }

        return $sandi;
    }
}
