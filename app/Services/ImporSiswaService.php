<?php

namespace App\Services;

use App\Models\RiwayatTindakanAdmin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImporSiswaService
{
    /**
     * Baca dan validasi berkas spreadsheet siswa untuk tahap pratinjau.
     * Ukur waktu pembacaan dan pemrosesan dalam milidetik.
     *
     * @return array{
     *     baris_valid: array,
     *     baris_galat: array,
     *     total_baris: int,
     *     durasi_ms: float,
     *     nama_berkas: string
     * }
     */
    public function pratinjau(UploadedFile $file): array
    {
        $mulai = microtime(true);

        $path = $file->getRealPath();
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $semuaBaris = $worksheet->toArray(null, false, false, false);

        if (empty($semuaBaris)) {
            throw new \InvalidArgumentException('Berkas Excel kosong atau tidak memiliki data.');
        }

        // Ambil header pada baris 1
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($semuaBaris));
        $indeksNama = array_search('nama_lengkap', $header);
        $indeksUsername = array_search('username', $header);
        $indeksEmail = array_search('email', $header);
        $indeksKelas = array_search('kelas', $header);

        if ($indeksNama === false || $indeksUsername === false || $indeksEmail === false) {
            throw new \InvalidArgumentException('Berkas tidak memiliki kolom wajib: nama_lengkap, username, email.');
        }

        // Batas maksimal 100 baris data (disesuaikan agar tahap konfirmasi aman dari timeout tanpa menurunkan biaya hash)
        if (count($semuaBaris) > 100) {
            throw new \InvalidArgumentException('Jumlah baris data melebihi batas maksimal 100 baris.');
        }

        $barisValid = [];
        $barisGalat = [];

        // Ambil semua username dan email yang sudah ada di database untuk deteksi cepat
        $existingUsernames = User::pluck('username')->map(fn ($u) => strtolower($u))->flip()->all();
        $existingEmails = User::pluck('email')->map(fn ($e) => strtolower($e))->flip()->all();

        $usernamesDalamBerkas = [];
        $emailsDalamBerkas = [];

        foreach ($semuaBaris as $no => $kolom) {
            $nomorBaris = $no + 2; // Baris 1 adalah header di Excel
            $nama = trim((string) ($kolom[$indeksNama] ?? ''));
            $username = trim((string) ($kolom[$indeksUsername] ?? ''));
            $email = trim((string) ($kolom[$indeksEmail] ?? ''));
            $kelas = $indeksKelas !== false ? trim((string) ($kolom[$indeksKelas] ?? '')) : null;

            // Lewati jika seluruh baris kosong
            if ($nama === '' && $username === '' && $email === '') {
                continue;
            }

            // Periksa keberadaan rumus Excel (dimulai dengan '=') di semua kolom
            $adaRumus = false;
            foreach ([$nama, $username, $email, $kelas] as $nilaiKolom) {
                if ($nilaiKolom !== null && str_starts_with($nilaiKolom, '=')) {
                    $adaRumus = true;
                    break;
                }
            }

            if ($adaRumus) {
                $alasanGalat[] = 'rumus tidak diizinkan.';
            }

            if ($nama === '') {
                $alasanGalat[] = 'Nama lengkap wajib diisi.';
            }

            if ($username === '') {
                $alasanGalat[] = 'Username wajib diisi.';
            } elseif (! preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                $alasanGalat[] = 'Username hanya boleh berisi huruf, angka, strip, atau garis bawah.';
            } else {
                $uLower = strtolower($username);
                if (isset($existingUsernames[$uLower])) {
                    $alasanGalat[] = "Username '{$username}' sudah terdaftar di sistem.";
                } elseif (isset($usernamesDalamBerkas[$uLower])) {
                    $alasanGalat[] = "Username '{$username}' duplikat di dalam berkas (baris {$usernamesDalamBerkas[$uLower]}).";
                } else {
                    $usernamesDalamBerkas[$uLower] = $nomorBaris;
                }
            }

            if ($email === '') {
                $alasanGalat[] = 'Email wajib diisi.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alasanGalat[] = 'Format email tidak valid.';
            } else {
                $eLower = strtolower($email);
                if (isset($existingEmails[$eLower])) {
                    $alasanGalat[] = "Email '{$email}' sudah terdaftar di sistem.";
                } elseif (isset($emailsDalamBerkas[$eLower])) {
                    $alasanGalat[] = "Email '{$email}' duplikat di dalam berkas (baris {$emailsDalamBerkas[$eLower]}).";
                } else {
                    $emailsDalamBerkas[$eLower] = $nomorBaris;
                }
            }

            $dataBaris = [
                'nomor_baris' => $nomorBaris,
                'nama_lengkap' => $nama,
                'username' => $username,
                'email' => $email,
                'kelas' => $kelas ?: null,
            ];

            if (! empty($alasanGalat)) {
                $dataBaris['alasan'] = implode(' ', $alasanGalat);
                $barisGalat[] = $dataBaris;
            } else {
                $barisValid[] = $dataBaris;
            }
        }

        $durasiMs = round((microtime(true) - $mulai) * 1000, 2);

        return [
            'baris_valid' => $barisValid,
            'baris_galat' => $barisGalat,
            'total_baris' => count($barisValid) + count($barisGalat),
            'durasi_ms' => $durasiMs,
            'nama_berkas' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Simpan baris-baris valid ke basis data dalam satu transaksi atomik.
     * Buat kata sandi acak 10 karakter untuk masing-masing siswa dan catat riwayat tindakan admin.
     *
     * @return array Daftar akun yang berhasil dibuat dengan kata sandi sementaranya
     */
    public function konfirmasi(array $barisValid, int $adminId, string $namaBerkas, int $jumlahGagal = 0): array
    {
        if (empty($barisValid)) {
            return [];
        }

        $hasilAkun = [];

        DB::transaction(function () use ($barisValid, $adminId, $namaBerkas, $jumlahGagal, &$hasilAkun) {
            foreach ($barisValid as $baris) {
                $kataSandiSementara = Str::password(10, true, true, false, false);

                $siswa = User::create([
                    'nama_lengkap' => $baris['nama_lengkap'],
                    'username' => $baris['username'],
                    'email' => $baris['email'],
                    'kelas' => $baris['kelas'] ?? null,
                    'password' => Hash::make($kataSandiSementara),
                    'peran' => 'siswa',
                    'aktif' => true,
                    'wajib_ganti_password' => true,
                ]);

                $hasilAkun[] = [
                    'id' => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'username' => $siswa->username,
                    'email' => $siswa->email,
                    'kelas' => $siswa->kelas,
                    'kata_sandi_sementara' => $kataSandiSementara,
                ];
            }

            $jumlahBerhasil = count($hasilAkun);
            RiwayatTindakanAdmin::create([
                'admin_id' => $adminId,
                'siswa_id' => null,
                'jenis' => 'impor_siswa',
                'keterangan' => "Mengimpor {$jumlahBerhasil} siswa baru dari berkas '{$namaBerkas}'. Gagal: {$jumlahGagal}.",
            ]);
        });

        return $hasilAkun;
    }
}
