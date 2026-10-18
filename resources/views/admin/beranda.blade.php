<x-layouts.admin judul="Dasbor Admin">
    <div class="space-y-8">
        <!-- Judul Halaman -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Dasbor Pengelola</h1>
            <p class="text-muted text-sm mt-1">Selamat datang, {{ auth()->user()->nama_lengkap }}. Ringkasan progres pembelajaran dan aktivitas penelitian.</p>
        </div>

        <!-- 1. Empat Kartu Ringkasan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Siswa Terdaftar -->
            <x-kartu class="p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Siswa Terdaftar</span>
                    <x-ikon nama="users" ukuran="20" kelas="text-muted" />
                </div>
                <p class="text-3xl font-black text-ink mt-2">
                    {{ $jumlahSiswaAktif + $jumlahSiswaNonaktif }}
                </p>
                <div class="text-xs text-muted mt-1 flex items-center gap-2">
                    <span class="text-green-700 font-bold">{{ $jumlahSiswaAktif }} aktif</span>
                    <span>&bull;</span>
                    <span class="text-rose-600 font-bold">{{ $jumlahSiswaNonaktif }} nonaktif</span>
                </div>
            </x-kartu>

            <!-- Siswa Masuk Hari Ini -->
            <x-kartu class="p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Masuk Hari Ini</span>
                    <x-ikon nama="user-check" ukuran="20" kelas="text-muted" />
                </div>
                <p class="text-3xl font-black text-green-700 mt-2">
                    {{ $siswaMasukHariIni }}
                </p>
                <span class="text-xs text-muted mt-1 block">siswa aktif (WIB)</span>
            </x-kartu>

            <!-- Sesi Akses Hari Ini -->
            <x-kartu class="p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Sesi Hari Ini</span>
                    <x-ikon nama="clock" ukuran="20" kelas="text-muted" />
                </div>
                <p class="text-3xl font-black text-sky-700 mt-2">
                    {{ $jumlahSesiHariIni }}
                </p>
                <span class="text-xs text-muted mt-1 block">total sesi login</span>
            </x-kartu>

            <!-- Esai Menunggu Penilaian -->
            <x-kartu class="p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-muted uppercase tracking-wider">Antrean Esai</span>
                        <x-ikon nama="file-edit" ukuran="20" kelas="{{ $esaiMenungguNilai > 0 ? 'text-amber-500' : 'text-muted' }}" />
                    </div>
                    <p class="text-3xl font-black {{ $esaiMenungguNilai > 0 ? 'text-amber-600' : 'text-green-700' }} mt-2">
                        {{ $esaiMenungguNilai }}
                    </p>
                    <span class="text-xs text-muted mt-1 block">belum dinilai</span>
                </div>
                @if ($esaiMenungguNilai > 0)
                    <div class="mt-3 pt-2 border-t border-border">
                        <a href="{{ route('admin.penilaian-esai.index') }}" class="text-xs font-bold text-amber-700 hover:underline flex items-center gap-1">
                            <span>Periksa Antrean</span>
                            <x-ikon nama="arrow-right" ukuran="14" />
                        </a>
                    </div>
                @endif
            </x-kartu>
        </div>

        <!-- 2. Sebaran Progres Pembelajaran (Batang CSS Sederhana) -->
        <x-kartu class="p-6 space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-ink">Sebaran Progres Pembelajaran</h2>
                <p class="text-xs text-muted">Distribusi tahapan belajar yang sedang aktif dihadapi oleh {{ $totalSiswa }} siswa.</p>
            </div>

            <div class="space-y-3 pt-2">
                @foreach ($sebaranProgres as $tahap => $jumlah)
                    @php
                        $persen = $totalSiswa > 0 ? round(($jumlah / $totalSiswa) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-ink">{{ $tahap }}</span>
                            <span class="text-muted font-bold">{{ $jumlah }} siswa ({{ $persen }}%)</span>
                        </div>
                        <div class="w-full bg-border rounded-full h-2.5 overflow-hidden">
                            <div class="bg-green-700 h-full rounded-full transition-all duration-300" style="width: {{ $persen }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-kartu>

        <!-- 3. Tabel Rata-rata Nilai per Kuis -->
        <x-kartu class="p-6 space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-ink">Rata-rata Nilai per Kuis</h2>
                <p class="text-xs text-muted">Dihitung dari nilai tertinggi per siswa yang telah tuntas dinilai (esai tertunda tidak diikutsertakan sampai dinilai).</p>
            </div>

            <div class="overflow-x-auto focus:outline-none focus:ring-1 focus:ring-green-700 rounded-xl" tabindex="0" role="region" aria-label="Statistik Kuis Aktif">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-border text-muted uppercase font-bold">
                            <th class="py-2.5 px-3">Kuis</th>
                            <th class="py-2.5 px-3">Tipe</th>
                            <th class="py-2.5 px-3 text-center">Siswa Tuntas</th>
                            <th class="py-2.5 px-3 text-right">Rata-rata Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($statistikKuis as $stat)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-ink">{{ $stat['judul'] }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface border border-border text-muted uppercase">
                                        {{ $stat['tipe'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center font-bold">{{ $stat['jumlah_siswa'] }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-extrabold text-sm text-green-800">
                                    {{ $stat['rata_rata'] !== null ? $stat['rata_rata'] : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted">Belum ada data kuis aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>

        <!-- 4. 10 Aktivitas Penyerahan Kuis Terakhir -->
        <x-kartu class="p-6 space-y-4">
            <div>
                <h2 class="text-base font-extrabold text-ink">Aktivitas Penyerahan Kuis Terbaru</h2>
                <p class="text-xs text-muted">10 kuis terakhir yang diserahkan oleh siswa.</p>
            </div>

            <div class="overflow-x-auto focus:outline-none focus:ring-1 focus:ring-green-700 rounded-xl" tabindex="0" role="region" aria-label="Aktivitas Penyerahan Kuis Terbaru">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-border text-muted uppercase font-bold">
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Siswa</th>
                            <th class="py-2.5 px-3">Kuis</th>
                            <th class="py-2.5 px-3 text-right">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($aktivitasTerbaru as $akt)
                            <tr>
                                <td class="py-2.5 px-3 text-muted whitespace-nowrap">
                                    {{ $akt->waktu_selesai ? \Carbon\Carbon::parse($akt->waktu_selesai)->translatedFormat('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-2.5 px-3 font-bold text-ink whitespace-nowrap">
                                    {{ $akt->user?->nama_lengkap ?? 'Siswa' }}
                                </td>
                                <td class="py-2.5 px-3 text-muted">
                                    {{ $akt->kuis?->judul ?? 'Kuis' }} (ke-{{ $akt->nomor_percobaan }})
                                </td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    @if ($akt->nilai_akhir !== null)
                                        <span class="font-bold text-ink">{{ $akt->nilai_akhir }}</span>
                                    @elseif ($akt->esai_menunggu_nilai)
                                        <span class="text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Menunggu Esai
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted">Belum ada aktivitas penyerahan kuis.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
