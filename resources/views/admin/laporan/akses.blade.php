<x-layouts.admin judul="{{ __('laporan.judul_laporan_akses') }}">
    <div class="space-y-6">
        <!-- Header Halaman & Tombol Unduh -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-surface text-muted mb-2">
                    Instrumen Penelitian
                </span>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">
                    {{ __('laporan.judul_laporan_akses') }}
                </h1>
                <p class="text-sm text-muted mt-1">
                    Catatan kehadiran, waktu akses, dan durasi aktif belajar siswa per sesi.
                </p>
            </div>

            <div>
                <x-tombol tipe="utama" href="{{ route('admin.laporan.akses.ekspor', request()->query()) }}" class="w-full sm:w-auto">
                    <x-ikon nama="download" ukuran="16" />
                    <span>{{ __('laporan.unduh_excel') }}</span>
                </x-tombol>
            </div>
        </div>

        <!-- Kartu Ringkasan Statistik Agregat -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu class="p-5 border border-border">
                <span class="text-xs font-extrabold text-muted uppercase tracking-wider block">
                    {{ __('laporan.ringkasan_sesi') }}
                </span>
                <div class="text-2xl font-black text-ink mt-1">
                    {{ number_format($totalSesi) }}
                </div>
            </x-kartu>

            <x-kartu class="p-5 border border-border">
                <span class="text-xs font-extrabold text-muted uppercase tracking-wider block">
                    {{ __('laporan.ringkasan_durasi') }}
                </span>
                <div class="text-2xl font-black text-ink mt-1">
                    @php
                        $jam = floor($totalDurasiDetik / 3600);
                        $menit = floor(($totalDurasiDetik % 3600) / 60);
                        $detik = $totalDurasiDetik % 60;
                    @endphp
                    @if ($jam > 0)
                        {{ $jam }}j {{ $menit }}m
                    @else
                        {{ $menit }}m {{ $detik }}d
                    @endif
                </div>
            </x-kartu>

            <x-kartu class="p-5 border border-border">
                <span class="text-xs font-extrabold text-muted uppercase tracking-wider block">
                    {{ __('laporan.ringkasan_siswa') }}
                </span>
                <div class="text-2xl font-black text-ink mt-1">
                    {{ number_format($totalSiswaUnik) }}
                </div>
            </x-kartu>
        </div>

        <!-- Filter & Tab Navigasi -->
        <x-kartu class="p-5 border border-border space-y-4">
            <form method="GET" action="{{ route('admin.laporan.akses') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div>
                    <label for="tanggal_mulai" class="block text-xs font-bold text-muted mb-1">{{ __('laporan.filter_tanggal_mulai') }}</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ $tanggalMulai }}"
                        class="w-full text-xs rounded-xl border border-border px-3 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div>
                    <label for="tanggal_selesai" class="block text-xs font-bold text-muted mb-1">{{ __('laporan.filter_tanggal_selesai') }}</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ $tanggalSelesai }}"
                        class="w-full text-xs rounded-xl border border-border px-3 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div>
                    <label for="cari_siswa" class="block text-xs font-bold text-muted mb-1">{{ __('laporan.filter_cari_siswa') }}</label>
                    <input type="text" id="cari_siswa" name="cari" value="{{ $cari }}" placeholder="Nama / Username..."
                        class="w-full text-xs rounded-xl border border-border px-3 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div class="flex items-end gap-2">
                    <x-tombol tipe="garis" type="submit" class="!py-2 text-xs w-full justify-center">
                        <x-ikon nama="search" ukuran="14" />
                        <span>Filter</span>
                    </x-tombol>

                    @if ($tanggalMulai || $tanggalSelesai || $cari)
                        <x-tombol tipe="garis" href="{{ route('admin.laporan.akses', ['tab' => $tab]) }}" class="!py-2 text-xs !px-2.5">
                            Reset
                        </x-tombol>
                    @endif
                </div>
            </form>

            <!-- Navigasi Tab -->
            <div class="flex border-b border-border gap-2 pt-2">
                <a href="{{ route('admin.laporan.akses', array_merge(request()->query(), ['tab' => 'riwayat'])) }}"
                    class="pb-2.5 px-3 text-xs font-black border-b-2 transition-colors {{ $tab === 'riwayat' ? 'border-green-700 text-green-700' : 'border-transparent text-muted hover:text-ink' }}">
                    {{ __('laporan.tab_riwayat') }}
                </a>
                <a href="{{ route('admin.laporan.akses', array_merge(request()->query(), ['tab' => 'rekap'])) }}"
                    class="pb-2.5 px-3 text-xs font-black border-b-2 transition-colors {{ $tab === 'rekap' ? 'border-green-700 text-green-700' : 'border-transparent text-muted hover:text-ink' }}">
                    {{ __('laporan.tab_rekap') }}
                </a>
            </div>
        </x-kartu>

        <!-- Wadah Tabel Responsif (Bisa digulir mendatar di HP) -->
        <x-kartu class="overflow-hidden border border-border p-0">
            <div class="overflow-x-auto focus:outline-none focus:ring-1 focus:ring-green-700" tabindex="0" role="region" aria-label="Tabel Laporan Akses Siswa">
                @if ($tab === 'riwayat')
                    <table class="w-full text-left text-xs text-ink">
                        <thead class="bg-surface border-b border-border text-muted font-black uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">{{ __('laporan.nama_lengkap') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.kelas') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.waktu_akses') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.aktivitas_terakhir') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('laporan.durasi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($dataRiwayat as $item)
                                <tr class="hover:bg-green-50/50 transition-colors">
                                    <td class="px-4 py-3 font-bold text-ink">
                                        {{ $item->user?->nama_lengkap ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ $item->user?->kelas ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-muted font-medium">
                                        {{ $item->waktu_masuk?->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB
                                    </td>
                                    <td class="px-4 py-3 text-muted font-medium">
                                        {{ $item->aktivitas_terakhir?->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-ink">
                                        @php
                                            $jm = floor($item->durasi_detik / 3600);
                                            $mn = floor(($item->durasi_detik % 3600) / 60);
                                            $dt = $item->durasi_detik % 60;
                                        @endphp
                                        @if ($jm > 0)
                                            {{ $jm }}j {{ $mn }}m {{ $dt }}d
                                        @elseif ($mn > 0)
                                            {{ $mn }}m {{ $dt }}d
                                        @else
                                            {{ $dt }} detik
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted">
                                        Tidak ada catatan aktivitas akses pada rentang ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($dataRiwayat->hasPages())
                        <div class="p-4 border-t border-border">
                            {{ $dataRiwayat->links() }}
                        </div>
                    @endif
                @else
                    <!-- Tab Rekap per Siswa -->
                    <table class="w-full text-left text-xs text-ink">
                        <thead class="bg-surface border-b border-border text-muted font-black uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">{{ __('laporan.nama_lengkap') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.kelas') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('laporan.jumlah_sesi') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('laporan.ringkasan_durasi') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.akses_pertama') }}</th>
                                <th class="px-4 py-3">{{ __('laporan.aktivitas_terakhir') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($dataRekap as $siswa)
                                <tr class="hover:bg-green-50/50 transition-colors">
                                    <td class="px-4 py-3 font-bold text-ink">
                                        {{ $siswa->nama_lengkap }}
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ $siswa->kelas ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-ink">
                                        {{ (int) ($siswa->jumlah_sesi ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-ink">
                                        @php
                                            $tot = (int) ($siswa->total_durasi ?? 0);
                                            $jm = floor($tot / 3600);
                                            $mn = floor(($tot % 3600) / 60);
                                            $dt = $tot % 60;
                                        @endphp
                                        @if ($jm > 0)
                                            {{ $jm }}j {{ $mn }}m
                                        @elseif ($mn > 0)
                                            {{ $mn }}m {{ $dt }}d
                                        @else
                                            {{ $dt }} detik
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        @if ($siswa->akses_pertama)
                                            {{ \Illuminate\Support\Carbon::parse($siswa->akses_pertama)->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB
                                        @else
                                            <span class="text-amber-700 font-bold">{{ __('laporan.belum_pernah_akses') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        @if ($siswa->akses_terakhir)
                                            {{ \Illuminate\Support\Carbon::parse($siswa->akses_terakhir)->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-muted">
                                        Tidak ada data siswa ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($dataRekap->hasPages())
                        <div class="p-4 border-t border-border">
                            {{ $dataRekap->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
