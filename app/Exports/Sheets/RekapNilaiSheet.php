<?php

namespace App\Exports\Sheets;

use App\Exports\AmanValueBinder;
use App\Models\Bab;
use App\Models\Kuis;
use App\Models\User;
use App\Services\PenilaianKuisService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapNilaiSheet extends AmanValueBinder implements FromQuery, WithChunkReading, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    /**
     * @var Collection<int, Bab>
     */
    protected $babTerbit;

    protected ?Kuis $pretest;

    protected ?Kuis $posttest;

    protected PenilaianKuisService $penilaianService;

    public function __construct(
        protected ?string $cari = null,
        protected ?string $kelas = null
    ) {
        $this->babTerbit = Bab::where('status_terbit', true)
            ->with(['kuis' => fn ($q) => $q->where('tipe_kuis', 'kuis_bab')])
            ->orderBy('urutan')
            ->get();
        $this->pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        $this->posttest = Kuis::where('tipe_kuis', 'posttest')->first();
        $this->penilaianService = app(PenilaianKuisService::class);
    }

    public function query(): Builder
    {
        return User::query()
            ->where('peran', 'siswa')
            ->when($this->cari, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nama_lengkap', 'like', "%{$this->cari}%")
                        ->orWhere('username', 'like', "%{$this->cari}%");
                });
            })
            ->when($this->kelas, fn ($q) => $q->where('kelas', $this->kelas))
            ->with(['percobaanKuis' => function ($q) {
                $q->where('status_pengerjaan', 'selesai')
                    ->where('dibatalkan', false);
            }])
            ->orderBy('nama_lengkap', 'asc');
    }

    public function headings(): array
    {
        $headers = [
            'Nama Lengkap',
            'Kelas',
            'Pretest (Nilai)',
            'Pretest (Status)',
        ];

        foreach ($this->babTerbit as $bab) {
            $headers[] = $bab->judul.' (Nilai)';
            $headers[] = $bab->judul.' (Status)';
        }

        $headers[] = 'Posttest (Nilai)';
        $headers[] = 'Posttest (Status)';

        return $headers;
    }

    /**
     * @param  User  $row
     */
    public function map($row): array
    {
        $data = [
            $row->nama_lengkap,
            $row->kelas ?? '-',
        ];

        // 1. Pretest
        $this->tambahkanNilaiDanStatus($data, $row, $this->pretest);

        // 2. Kuis Tiap Bab Terbit
        foreach ($this->babTerbit as $bab) {
            $kuisBab = $bab->kuis;
            $this->tambahkanNilaiDanStatus($data, $row, $kuisBab);
        }

        // 3. Posttest
        $this->tambahkanNilaiDanStatus($data, $row, $this->posttest);

        return $data;
    }

    protected function tambahkanNilaiDanStatus(array &$data, User $siswa, ?Kuis $kuis): void
    {
        if (! $kuis) {
            $data[] = '-';
            $data[] = 'tidak ada kuis';

            return;
        }

        $percobaanSiswa = $siswa->percobaanKuis->where('kuis_id', $kuis->id);

        if ($percobaanSiswa->isEmpty()) {
            $data[] = '-';
            $data[] = 'belum dikerjakan';

            return;
        }

        // Cek apakah ada esai yang masih menunggu penilaian
        $adaEsaiMenunggu = $percobaanSiswa->contains('esai_menunggu_nilai', true);
        $nilaiTertinggi = $percobaanSiswa->map(fn ($p) => ! is_null($p->nilai_akhir) ? (float) $p->nilai_akhir : (! is_null($p->nilai_otomatis) ? (float) $p->nilai_otomatis : 0.00))->max();

        if ($adaEsaiMenunggu) {
            $data[] = $nilaiTertinggi !== null ? (float) $nilaiTertinggi : '-';
            $data[] = 'menunggu penilaian esai';
        } else {
            $data[] = $nilaiTertinggi !== null ? (float) $nilaiTertinggi : 0;
            $data[] = 'selesai dinilai';
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function title(): string
    {
        return 'Rekap Nilai';
    }
}
