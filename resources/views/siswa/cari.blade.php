<x-layouts.siswa :judul="'Pencarian: ' . e($kataKunci)">
    <div class="space-y-6">
        <div>
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Hasil Pencarian</span>
            <h1 class="text-2xl font-extrabold text-ink mt-0.5">
                Pencarian: "{{ e($kataKunci) }}"
            </h1>
        </div>

        <!-- Form Pencarian Ulang -->
        <form method="GET" action="{{ route('siswa.cari') }}" class="relative max-w-xl">
            <input type="text" name="q" value="{{ $kataKunci }}" minlength="2" required
                placeholder="Cari materi atau bab..."
                class="w-full rounded-2xl border border-border pl-11 pr-24 py-3 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm shadow-sm">
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                <x-ikon nama="search" ukuran="18" />
            </div>
            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 rounded-xl text-xs font-extrabold bg-green-700 text-white hover:bg-green-800 transition-colors">
                Cari
            </button>
        </form>

        @if ($terlaluPendek)
            <x-pita-info tipe="peringatan">
                Masukkan minimal 2 karakter untuk melakukan pencarian.
            </x-pita-info>
        @else
            @php
                $totalDitemukan = $hasilBab->count() + $hasilMateri->count();
            @endphp

            @if ($totalDitemukan === 0)
                <x-kartu class="p-8 text-center text-muted space-y-2">
                    <x-ikon nama="search-x" ukuran="36" class="mx-auto text-muted/60" />
                    <h2 class="text-base font-extrabold text-ink">Tidak Ditemukan</h2>
                    <p class="text-xs max-w-md mx-auto">
                        Tidak ada materi atau bab terbuka yang cocok dengan kata kunci "{{ e($kataKunci) }}".
                    </p>
                </x-kartu>
            @else
                <div class="space-y-6">
                    <!-- Hasil Bab -->
                    @if ($hasilBab->isNotEmpty())
                        <div class="space-y-3">
                            <h2 class="text-sm font-extrabold text-muted uppercase tracking-wider">
                                Bab Pembelajaran ({{ $hasilBab->count() }})
                            </h2>
                            <div class="grid grid-cols-1 gap-3">
                                @foreach ($hasilBab as $bab)
                                    <a href="{{ route('siswa.bab.show', $bab) }}"
                                        class="p-4 bg-white rounded-2xl border border-border hover:border-green-700 transition-colors flex items-center justify-between gap-4">
                                        <div class="space-y-0.5 min-w-0">
                                            <span class="text-xs font-bold text-green-700">Bab {{ $bab->urutan }}</span>
                                            <h3 class="text-base font-extrabold text-ink truncate">{{ $bab->judul }}</h3>
                                            @if ($bab->deskripsi)
                                                <p class="text-xs text-muted truncate">{{ $bab->deskripsi }}</p>
                                            @endif
                                        </div>
                                        <x-ikon nama="chevron-right" ukuran="18" class="text-muted shrink-0" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Hasil Materi -->
                    @if ($hasilMateri->isNotEmpty())
                        <div class="space-y-3">
                            <h2 class="text-sm font-extrabold text-muted uppercase tracking-wider">
                                Materi Pembelajaran ({{ $hasilMateri->count() }})
                            </h2>
                            <div class="grid grid-cols-1 gap-3">
                                @foreach ($hasilMateri as $materi)
                                    <a href="{{ route('siswa.bab.materi.show', [$materi->bab, $materi]) }}"
                                        class="p-4 bg-white rounded-2xl border border-border hover:border-green-700 transition-colors flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $materi->tipe === 'pdf' ? 'bg-red-50 text-red-700' : 'bg-red-50 text-red-600' }}">
                                                @if ($materi->tipe === 'pdf')
                                                    <x-ikon nama="file-text" ukuran="20" />
                                                @else
                                                    <x-ikon nama="video" ukuran="20" />
                                                @endif
                                            </div>
                                            <div class="space-y-0.5 min-w-0">
                                                <span class="text-[11px] font-bold text-muted">{{ $materi->bab?->judul }} &bull; Materi {{ $materi->urutan }}</span>
                                                <h3 class="text-sm font-extrabold text-ink truncate">{{ $materi->judul }}</h3>
                                            </div>
                                        </div>
                                        <x-ikon nama="chevron-right" ukuran="18" class="text-muted shrink-0" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</x-layouts.siswa>
