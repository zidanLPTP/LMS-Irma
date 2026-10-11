<x-layouts.siswa judul="Beranda Pembelajaran">
    <div class="space-y-8">
        <!-- Kartu Sambutan & Ringkasan Progres -->
        <x-kartu class="p-6 sm:p-8 bg-white border border-border">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-green-50 text-green-700 border border-green-200">
                        <x-ikon nama="leaf" ukuran="14" />
                        Alur Belajar Mandiri
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                        Halo, {{ auth()->user()->nama_lengkap }}!
                    </h1>
                    <p class="text-sm text-muted max-w-xl">
                        Selamat datang di modul pembelajaran biologi. Ikuti setiap tahapan materi dan kuis secara berurutan untuk menyelesaikan penelitian ini.
                    </p>
                </div>

                <div class="bg-surface rounded-2xl p-5 border border-border min-w-[260px] space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold text-muted">
                        <span>Progres Keseluruhan</span>
                        <span class="text-green-700 font-extrabold text-sm">{{ $persentaseProgres }}%</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-border rounded-full h-3 overflow-hidden">
                        <div class="bg-green-700 h-full rounded-full transition-all duration-500" style="width: {{ $persentaseProgres }}%"></div>
                    </div>

                    <div class="text-xs text-muted font-bold flex justify-between">
                        <span>{{ $langkahSelesai }} dari {{ $totalLangkah }} langkah selesai</span>
                    </div>

                    @if ($langkahLanjutan)
                        <div class="pt-2">
                            @if ($langkahLanjutan['tipe'] === 'bab')
                                <x-tombol tipe="utama" href="{{ route('siswa.bab.show', $langkahLanjutan['model']) }}" class="w-full justify-center !min-h-[42px] text-xs">
                                    <span>Lanjutkan Belajar</span>
                                    <x-ikon nama="arrow-right" ukuran="16" />
                                </x-tombol>
                            @else
                                <button type="button" disabled class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-surface text-muted border border-border cursor-not-allowed">
                                    <span>{{ $langkahLanjutan['judul'] }} (Segera tersedia)</span>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="pt-2 text-center text-xs font-bold text-green-700">
                            Semua tahapan belajar telah tuntas!
                        </div>
                    @endif
                </div>
            </div>
        </x-kartu>

        <!-- Stepper Alur Belajar (Mobile: Vertikal, Laptop: Grid / Horizontal Stepper) -->
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-extrabold text-ink">Tahapan Pembelajaran</h2>
                <p class="text-xs text-muted">Langkah terbuka akan membuka materi berikutnya setelah Anda menyelesaikan tahapan sebelumnya.</p>
            </div>

            <!-- Stepper List -->
            <div class="space-y-3">
                @foreach ($daftarLangkah as $index => $item)
                    @php
                        $isSelesai = $item['status'] === 'selesai';
                        $isTerbuka = $item['status'] === 'terbuka';
                        $isTerkunci = $item['status'] === 'terkunci';
                    @endphp

                    <div class="p-4 sm:p-5 rounded-2xl border transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4
                        {{ $isTerbuka ? 'bg-white border-green-700 shadow-[0_2px_8px_rgba(22,101,52,0.08)]' : ($isSelesai ? 'bg-white border-border' : 'bg-surface/50 border-border opacity-70') }}">
                        
                        <div class="flex items-start sm:items-center gap-4">
                            <!-- Indikator Status (Ikon Lucide + Bentuk) -->
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                {{ $isSelesai ? 'bg-green-100 text-green-700' : ($isTerbuka ? 'bg-green-700 text-white' : 'bg-border text-muted') }}">
                                @if ($isSelesai)
                                    <x-ikon nama="check" ukuran="20" />
                                @elseif ($isTerbuka)
                                    @if ($item['tipe'] === 'bab')
                                        <x-ikon nama="book-open" ukuran="20" />
                                    @else
                                        <x-ikon nama="clipboard-list" ukuran="20" />
                                    @endif
                                @else
                                    <x-ikon nama="lock" ukuran="18" />
                                @endif
                            </div>

                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider
                                        {{ $isSelesai ? 'text-green-700' : ($isTerbuka ? 'text-green-700' : 'text-muted') }}">
                                        Langkah {{ $index + 1 }} &bull; {{ ucfirst($item['tipe']) }}
                                    </span>
                                </div>
                                <h3 class="text-base font-extrabold text-ink leading-snug">
                                    {{ $item['judul'] }}
                                </h3>
                                @if ($item['tipe'] === 'bab' && $item['model']->deskripsi)
                                    <p class="text-xs text-muted line-clamp-1">
                                        {{ $item['model']->deskripsi }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Tombol Aksi / Status Teks -->
                        <div class="flex items-center gap-3 sm:self-center pl-14 sm:pl-0">
                            @if ($isSelesai)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                    <x-ikon nama="check" ukuran="14" />
                                    Selesai
                                </span>
                                @if ($item['tipe'] === 'bab')
                                    <x-tombol tipe="garis" href="{{ route('siswa.bab.show', $item['model']) }}" class="!min-h-[38px] !px-3.5 !py-1 text-xs font-bold">
                                        Buka Bab
                                    </x-tombol>
                                @endif
                            @elseif ($isTerbuka)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                    Terbuka
                                </span>
                                @if ($item['tipe'] === 'bab')
                                    <x-tombol tipe="utama" href="{{ route('siswa.bab.show', $item['model']) }}" class="!min-h-[38px] !px-4 !py-1.5 text-xs font-bold">
                                        <span>Mulai Belajar</span>
                                        <x-ikon nama="arrow-right" ukuran="14" />
                                    </x-tombol>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-surface text-muted border border-border cursor-not-allowed">
                                        <span>Kuis Segera Tersedia</span>
                                    </button>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-surface text-muted border border-border">
                                    <x-ikon nama="lock" ukuran="12" />
                                    Terkunci
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.siswa>
