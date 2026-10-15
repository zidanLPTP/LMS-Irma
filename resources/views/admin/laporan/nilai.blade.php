<x-layouts.admin judul="{{ __('laporan.judul_laporan_nilai') }}">
    <div class="space-y-6">
        <!-- Header Halaman & Tombol Unduh -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-surface text-muted mb-2">
                    Instrumen Penelitian
                </span>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">
                    {{ __('laporan.judul_laporan_nilai') }}
                </h1>
                <p class="text-sm text-muted mt-1">
                    Rekapitulasi pencapaian hasil pretest, kuis bab materi biologi, dan posttest seluruh siswa.
                </p>
            </div>

            <div>
                <x-tombol tipe="utama" href="{{ route('admin.laporan.nilai.ekspor', request()->query()) }}" class="w-full sm:w-auto">
                    <x-ikon nama="download" ukuran="16" />
                    <span>{{ __('laporan.unduh_excel') }}</span>
                </x-tombol>
            </div>
        </div>

        <!-- Filter Siswa dan Kelas -->
        <x-kartu class="p-5 border border-border">
            <form method="GET" action="{{ route('admin.laporan.nilai') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-muted mb-1">{{ __('laporan.filter_cari_siswa') }}</label>
                    <input type="text" name="cari" value="{{ $cari }}" placeholder="Nama / Username..."
                        class="w-full text-xs rounded-xl border border-border px-3 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-muted mb-1">{{ __('laporan.kelas') }}</label>
                    <select name="kelas" class="w-full text-xs rounded-xl border border-border px-3 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">{{ __('laporan.filter_kelas') }}</option>
                        @foreach ($daftarKelas as $kls)
                            <option value="{{ $kls }}" {{ $kelasTerpilih === $kls ? 'selected' : '' }}>
                                {{ $kls }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-tombol tipe="garis" type="submit" class="!py-2 text-xs w-full justify-center">
                        <x-ikon nama="search" ukuran="14" />
                        <span>Filter</span>
                    </x-tombol>

                    @if ($cari || $kelasTerpilih)
                        <x-tombol tipe="garis" href="{{ route('admin.laporan.nilai') }}" class="!py-2 text-xs !px-2.5">
                            Reset
                        </x-tombol>
                    @endif
                </div>
            </form>
        </x-kartu>

        <!-- Wadah Tabel Responsif (Bisa digulir mendatar di HP) -->
        <x-kartu class="overflow-hidden border border-border p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-ink whitespace-nowrap">
                    <thead class="bg-surface border-b border-border text-muted font-black uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 sticky left-0 bg-surface z-10">{{ __('laporan.nama_lengkap') }}</th>
                            <th class="px-4 py-3">{{ __('laporan.kelas') }}</th>
                            <th class="px-4 py-3 text-center">Pretest</th>
                            @foreach ($babTerbit as $bab)
                                <th class="px-4 py-3 text-center">{{ $bab->judul }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-center">Posttest</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($dataSiswa as $siswa)
                            <tr class="hover:bg-green-50/50 transition-colors">
                                <td class="px-4 py-3 font-bold text-ink sticky left-0 bg-white z-10">
                                    {{ $siswa->nama_lengkap }}
                                </td>
                                <td class="px-4 py-3 text-muted">
                                    {{ $siswa->kelas ?? '-' }}
                                </td>

                                <!-- Pretest -->
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $percPre = $pretest ? $siswa->percobaanKuis->where('kuis_id', $pretest->id) : collect();
                                        $adaEsaiPre = $percPre->contains('esai_menunggu_nilai', true);
                                        $nilaiPre = null;
                                        if ($percPre->isNotEmpty()) {
                                            $nilaiPre = $percPre->map(fn ($p) => ! is_null($p->nilai_akhir) ? (float) $p->nilai_akhir : (! is_null($p->nilai_otomatis) ? (float) $p->nilai_otomatis : 0.00))->max();
                                        }
                                    @endphp
                                    @if ($percPre->isEmpty())
                                        <span class="text-muted italic">{{ __('laporan.belum_dikerjakan') }}</span>
                                    @elseif ($adaEsaiPre)
                                        <span class="text-amber-700 font-bold">{{ __('laporan.menunggu_penilaian_esai') }}</span>
                                    @else
                                        <span class="font-black text-ink">{{ number_format($nilaiPre ?? 0, 0) }}%</span>
                                    @endif
                                </td>

                                <!-- Kuis Bab Terbit Dinamis -->
                                @foreach ($babTerbit as $bab)
                                    <td class="px-4 py-3 text-center">
                                        @if (! $bab->kuis)
                                            <span class="text-muted">-</span>
                                        @else
                                            @php
                                                $percBab = $siswa->percobaanKuis->where('kuis_id', $bab->kuis->id);
                                                $adaEsaiBab = $percBab->contains('esai_menunggu_nilai', true);
                                                $nilaiBab = null;
                                                if ($percBab->isNotEmpty()) {
                                                    $nilaiBab = $percBab->map(fn ($p) => ! is_null($p->nilai_akhir) ? (float) $p->nilai_akhir : (! is_null($p->nilai_otomatis) ? (float) $p->nilai_otomatis : 0.00))->max();
                                                }
                                            @endphp
                                            @if ($percBab->isEmpty())
                                                <span class="text-muted italic">{{ __('laporan.belum_dikerjakan') }}</span>
                                            @elseif ($adaEsaiBab)
                                                <span class="text-amber-700 font-bold">{{ __('laporan.menunggu_penilaian_esai') }}</span>
                                            @else
                                                <span class="font-black {{ ($nilaiBab ?? 0) >= $bab->kuis->nilaiLulusEfektif() ? 'text-green-700' : 'text-ink' }}">
                                                    {{ number_format($nilaiBab ?? 0, 0) }}%
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach

                                <!-- Posttest -->
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $percPost = $posttest ? $siswa->percobaanKuis->where('kuis_id', $posttest->id) : collect();
                                        $adaEsaiPost = $percPost->contains('esai_menunggu_nilai', true);
                                        $nilaiPost = null;
                                        if ($percPost->isNotEmpty()) {
                                            $nilaiPost = $percPost->map(fn ($p) => ! is_null($p->nilai_akhir) ? (float) $p->nilai_akhir : (! is_null($p->nilai_otomatis) ? (float) $p->nilai_otomatis : 0.00))->max();
                                        }
                                    @endphp
                                    @if ($percPost->isEmpty())
                                        <span class="text-muted italic">{{ __('laporan.belum_dikerjakan') }}</span>
                                    @elseif ($adaEsaiPost)
                                        <span class="text-amber-700 font-bold">{{ __('laporan.menunggu_penilaian_esai') }}</span>
                                    @else
                                        <span class="font-black text-ink">{{ number_format($nilaiPost ?? 0, 0) }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($babTerbit) }}" class="px-4 py-8 text-center text-muted">
                                    Tidak ada data siswa ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dataSiswa->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $dataSiswa->links() }}
                </div>
            @endif
        </x-kartu>
    </div>
</x-layouts.admin>
