<?php

namespace App\Services;

use App\Models\Kuis;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImporSoalService
{
    /**
     * Baca dan validasi berkas spreadsheet soal kuis untuk tahap pratinjau.
     * Menggunakan aturan yang sama persis dengan SoalRequest.
     *
     * @return array{
     *     baris_valid: array,
     *     baris_galat: array,
     *     total_baris: int,
     *     nama_berkas: string
     * }
     */
    public function pratinjau(UploadedFile $file, Kuis $kuis): array
    {
        $path = $file->getRealPath();
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $semuaBaris = $worksheet->toArray(null, false, false, false);

        if (empty($semuaBaris)) {
            throw new \InvalidArgumentException('Berkas Excel kosong atau tidak memiliki data.');
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($semuaBaris));
        $indeksTipe = array_search('tipe', $header);
        $indeksPertanyaan = array_search('pertanyaan', $header);
        $indeksBobot = array_search('bobot', $header);

        if ($indeksTipe === false || $indeksPertanyaan === false || $indeksBobot === false) {
            throw new \InvalidArgumentException('Berkas tidak memiliki kolom wajib: tipe, pertanyaan, bobot.');
        }

        $indeksPilihanA = array_search('pilihan_a', $header);
        $indeksPilihanB = array_search('pilihan_b', $header);
        $indeksPilihanC = array_search('pilihan_c', $header);
        $indeksPilihanD = array_search('pilihan_d', $header);
        $indeksPilihanE = array_search('pilihan_e', $header);
        $indeksKunci = array_search('kunci', $header);
        $indeksKunci1 = array_search('kunci_isian_1', $header);
        $indeksKunci2 = array_search('kunci_isian_2', $header);
        $indeksKunci3 = array_search('kunci_isian_3', $header);

        if (count($semuaBaris) > 100) {
            throw new \InvalidArgumentException('Jumlah baris soal melebihi batas maksimal 100 baris.');
        }

        $barisValid = [];
        $barisGalat = [];

        foreach ($semuaBaris as $no => $kolom) {
            $nomorBaris = $no + 2;
            $tipe = strtolower(trim((string) ($kolom[$indeksTipe] ?? '')));
            $pertanyaan = trim((string) ($kolom[$indeksPertanyaan] ?? ''));
            $bobot = trim((string) ($kolom[$indeksBobot] ?? ''));

            if ($tipe === '' && $pertanyaan === '' && $bobot === '') {
                continue;
            }

            $alasanGalat = [];

            if (! in_array($tipe, ['pilihan_ganda', 'isian', 'esai'], true)) {
                $alasanGalat[] = "Tipe soal tidak valid ('{$tipe}'). Gunakan pilihan_ganda, isian, atau esai.";
            }

            if ($pertanyaan === '') {
                $alasanGalat[] = 'Pertanyaan wajib diisi.';
            }

            if ($bobot === '' || ! is_numeric($bobot) || (int) $bobot < 1) {
                $alasanGalat[] = 'Bobot poin harus berupa angka minimal 1.';
            }

            $detailSoal = [
                'nomor_baris' => $nomorBaris,
                'tipe' => $tipe,
                'pertanyaan' => $pertanyaan,
                'bobot' => (int) $bobot,
                'pilihan' => [],
                'kunci_pg' => null,
                'kunci_isian' => [],
            ];

            if ($tipe === 'pilihan_ganda') {
                $pilihanMap = [
                    'A' => $indeksPilihanA !== false ? trim((string) ($kolom[$indeksPilihanA] ?? '')) : '',
                    'B' => $indeksPilihanB !== false ? trim((string) ($kolom[$indeksPilihanB] ?? '')) : '',
                    'C' => $indeksPilihanC !== false ? trim((string) ($kolom[$indeksPilihanC] ?? '')) : '',
                    'D' => $indeksPilihanD !== false ? trim((string) ($kolom[$indeksPilihanD] ?? '')) : '',
                    'E' => $indeksPilihanE !== false ? trim((string) ($kolom[$indeksPilihanE] ?? '')) : '',
                ];

                $pilihanTerisi = array_filter($pilihanMap, fn ($teks) => $teks !== '');
                if (count($pilihanTerisi) < 2) {
                    $alasanGalat[] = 'Soal pilihan ganda wajib memiliki minimal 2 pilihan jawaban.';
                }

                $kunciPg = strtoupper(trim((string) ($kolom[$indeksKunci] ?? '')));
                if ($kunciPg === '' || ! isset($pilihanTerisi[$kunciPg])) {
                    $alasanGalat[] = "Kunci jawaban pilihan ganda ('{$kunciPg}') harus cocok dengan opsi pilihan yang terisi.";
                }

                $detailSoal['pilihan'] = $pilihanTerisi;
                $detailSoal['kunci_pg'] = $kunciPg;
            } elseif ($tipe === 'isian') {
                $kunciIsian = [];
                foreach ([$indeksKunci1, $indeksKunci2, $indeksKunci3] as $indeksK) {
                    if ($indeksK !== false) {
                        $teksK = trim((string) ($kolom[$indeksK] ?? ''));
                        if ($teksK !== '') {
                            $kunciIsian[] = $teksK;
                        }
                    }
                }

                if (empty($kunciIsian)) {
                    $alasanGalat[] = 'Soal isian wajib memiliki minimal 1 kunci jawaban yang benar.';
                }

                $detailSoal['kunci_isian'] = $kunciIsian;
            }

            if (! empty($alasanGalat)) {
                $detailSoal['alasan'] = implode(' ', $alasanGalat);
                $barisGalat[] = $detailSoal;
            } else {
                $barisValid[] = $detailSoal;
            }
        }

        return [
            'baris_valid' => $barisValid,
            'baris_galat' => $barisGalat,
            'total_baris' => count($barisValid) + count($barisGalat),
            'nama_berkas' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Konfirmasi impor soal secara ATOMIK (Semua atau tidak sama sekali).
     * Jika ada satu saja baris yang galat, lempar exception dan batalkan transaksi.
     */
    public function konfirmasi(Kuis $kuis, array $barisValid, array $barisGalat): int
    {
        if (! empty($barisGalat)) {
            throw new \InvalidArgumentException('Tidak dapat mengimpor: terdapat baris yang galat. Perbaiki berkas dan coba lagi.');
        }

        if (empty($barisValid)) {
            return 0;
        }

        return DB::transaction(function () use ($kuis, $barisValid) {
            $urutanTerakhir = (int) $kuis->soal()->max('urutan');
            $jumlahDitambahkan = 0;

            foreach ($barisValid as $item) {
                $urutanTerakhir++;

                $soal = Soal::create([
                    'kuis_id' => $kuis->id,
                    'tipe_soal' => $item['tipe'],
                    'pertanyaan' => $item['pertanyaan'],
                    'bobot_poin' => $item['bobot'],
                    'urutan' => $urutanTerakhir,
                    'kunci_jawaban' => $item['tipe'] === 'isian' ? $item['kunci_isian'] : null,
                ]);

                if ($item['tipe'] === 'pilihan_ganda') {
                    $urutanPilihan = 1;
                    foreach ($item['pilihan'] as $huruf => $teksOpsi) {
                        PilihanJawaban::create([
                            'soal_id' => $soal->id,
                            'teks_pilihan' => $teksOpsi,
                            'apakah_benar' => ($huruf === $item['kunci_pg']),
                            'urutan' => $urutanPilihan++,
                        ]);
                    }
                }

                $jumlahDitambahkan++;
            }

            return $jumlahDitambahkan;
        });
    }
}
