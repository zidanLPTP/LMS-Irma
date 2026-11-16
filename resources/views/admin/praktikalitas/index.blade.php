<x-layouts.admin judul="Hasil Praktikalitas Media">
    <div class="space-y-6">
        <!-- Header Halaman & Tombol Ekspor -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">Hasil Praktikalitas Penggunaan Web</h1>
                <p class="text-sm text-muted">Rekapitulasi respon siswa terhadap kepraktisan media pembelajaran BioSTEM-X (Skala Likert 1–4).</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <x-tombol tipe="utama" href="{{ route('admin.praktikalitas.ekspor') }}" class="gap-2">
                    <x-ikon nama="download" ukuran="16" />
                    <span>Ekspor Excel</span>
                </x-tombol>
            </div>
        </div>

        <!-- Kartu Metrik Utama Penelitian -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-border p-5 space-y-1 shadow-xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Responden</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-ink">{{ $totalResponden }}</span>
                    <span class="text-xs text-muted font-bold">siswa</span>
                </div>
                <p class="text-[11px] text-muted">Siswa yang telah mengisi instrumen</p>
            </div>

            <div class="bg-white rounded-2xl border border-border p-5 space-y-1 shadow-xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Persentase Kepraktisan</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-green-700">{{ $persenTotal }}%</span>
                </div>
                <p class="text-[11px] text-muted">Rata-rata skor dari skor ideal</p>
            </div>

            <div class="bg-white rounded-2xl border border-border p-5 space-y-1 shadow-xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Kategori Kepraktisan</span>
                <div>
                    @php
                        $warnaKategori = match ($kategoriTotal) {
                            'Sangat Praktis' => 'bg-green-100 text-green-800 border-green-300',
                            'Praktis' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'Cukup Praktis' => 'bg-amber-50 text-amber-800 border-amber-200',
                            default => 'bg-red-50 text-red-800 border-red-200',
                        };
                    @endphp
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold border mt-1 {{ $warnaKategori }}">
                        {{ $kategoriTotal }}
                    </span>
                </div>
                <p class="text-[11px] text-muted">Standar kriteria media pembelajaran</p>
            </div>

            <div class="bg-white rounded-2xl border border-border p-5 space-y-1 shadow-xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Butir Instrumen</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-ink">{{ $jumlahButir }}</span>
                    <span class="text-xs text-muted font-bold">butir pernyataan</span>
                </div>
                <p class="text-[11px] text-muted">4 aspek evaluasi media</p>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Per Butir -->
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden space-y-4">
            <div class="p-5 border-b border-border flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-ink">Rekapitulasi Skor Per Butir Instrumen</h2>
                    <p class="text-xs text-muted">Distribusi pilihan skala (4: SS, 3: S, 2: TS, 1: STS) dan persentase capaian per butir.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-surface border-b border-border text-muted font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 w-10 text-center">No</th>
                            <th class="px-4 py-3 w-36">Aspek</th>
                            <th class="px-4 py-3 min-w-[200px]">Pernyataan</th>
                            <th class="px-3 py-3 w-12 text-center" title="Sangat Setuju">SS (4)</th>
                            <th class="px-3 py-3 w-12 text-center" title="Setuju">S (3)</th>
                            <th class="px-3 py-3 w-12 text-center" title="Tidak Setuju">TS (2)</th>
                            <th class="px-3 py-3 w-12 text-center" title="Sangat Tidak Setuju">STS (1)</th>
                            <th class="px-3 py-3 w-16 text-center">Total Skor</th>
                            <th class="px-3 py-3 w-20 text-center">Persentase</th>
                            <th class="px-4 py-3 w-28 text-center">Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($rekapButir as $item)
                            <tr class="hover:bg-green-50/40 transition-colors">
                                <td class="px-4 py-3 text-center font-bold text-muted">{{ $item['urutan'] }}</td>
                                <td class="px-4 py-3 font-bold text-ink">{{ $item['aspek'] }}</td>
                                <td class="px-4 py-3 text-ink leading-relaxed">{{ $item['pernyataan'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-green-700">{{ $item['s4'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-emerald-600">{{ $item['s3'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-amber-600">{{ $item['s2'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-red-600">{{ $item['s1'] }}</td>
                                <td class="px-3 py-3 text-center font-extrabold text-ink">
                                    {{ $item['total_skor'] }} / {{ $item['skor_maks'] }}
                                </td>
                                <td class="px-3 py-3 text-center font-extrabold text-green-700">
                                    {{ $item['persen'] }}%
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $badgeKategori = match ($item['kategori']) {
                                            'Sangat Praktis' => 'bg-green-100 text-green-800',
                                            'Praktis' => 'bg-emerald-50 text-emerald-800',
                                            'Cukup Praktis' => 'bg-amber-50 text-amber-800',
                                            default => 'bg-red-50 text-red-800',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $badgeKategori }}">
                                        {{ $item['kategori'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-muted">
                                    Belum ada data instrumen. Jalankan seeder instrumen praktikalitas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($rekapButir->isNotEmpty())
                        <tfoot class="bg-surface font-extrabold border-t-2 border-border text-ink">
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-right">TOTAL KESELURUHAN:</td>
                                <td class="px-3 py-3 text-center">{{ $totalSkorSemua }} / {{ $totalMaksSemua }}</td>
                                <td class="px-3 py-3 text-center text-green-700">{{ $persenTotal }}%</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black {{ $warnaKategori }}">
                                        {{ $kategoriTotal }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Daftar Saran & Masukan Siswa -->
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-extrabold text-ink flex items-center gap-2">
                    <x-ikon nama="message-square" ukuran="18" class="text-green-700" />
                    <span>Saran dan Masukan Siswa (Kualitatif)</span>
                </h2>
                <p class="text-xs text-muted">Catatan atau komentar bebas yang diberikan siswa saat mengisi kuesioner.</p>
            </div>

            @if ($saranList->isEmpty())
                <div class="p-8 text-center text-xs text-muted">
                    Belum ada siswa yang mengirimkan saran tertulis.
                </div>
            @else
                <div class="divide-y divide-border text-xs">
                    @foreach ($saranList as $s)
                        <div class="p-5 space-y-1.5 hover:bg-surface/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-ink">
                                    {{ $s->user?->nama_lengkap ?? 'Siswa' }}
                                    <span class="text-muted font-normal">({{ $s->user?->kelas ?? 'Kelas -' }})</span>
                                </span>
                                <span class="text-[11px] text-muted">
                                    {{ $s->created_at?->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-ink text-sm bg-surface p-3 rounded-xl border border-border leading-relaxed">
                                "{{ $s->saran }}"
                            </p>
                        </div>
                    @endforeach
                </div>

                @if ($saranList->hasPages())
                    <div class="p-4 border-t border-border">
                        {{ $saranList->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.admin>
