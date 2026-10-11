<x-layouts.siswa judul="{{ $bab->judul }}">
    <div class="space-y-6">
        <!-- Header Bab & Tombol Kembali -->
        <div class="space-y-2">
            <a href="{{ route('siswa.beranda') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline">
                <x-ikon nama="chevron-left" ukuran="16" />
                <span>Kembali ke Beranda</span>
            </a>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                        Bab Pembelajaran
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight mt-0.5">
                        {{ $bab->judul }}
                    </h1>
                    @if ($bab->deskripsi)
                        <p class="text-sm text-muted mt-1 max-w-2xl">
                            {{ $bab->deskripsi }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Daftar Materi Sebagai Kartu -->
        <div class="space-y-4">
            <h2 class="text-lg font-extrabold text-ink">Materi Pembelajaran</h2>

            @if ($daftarMateri->isEmpty())
                <x-kartu class="p-8 text-center text-muted">
                    <p class="text-sm">Belum ada materi pembelajaran yang diterbitkan untuk bab ini.</p>
                </x-kartu>
            @else
                <div class="grid grid-cols-1 gap-3">
                    @foreach ($daftarMateri as $index => $materi)
                        @php
                            $sudahDibuka = isset($materiDibukaIds[$materi->id]);
                        @endphp

                        <a href="{{ route('siswa.bab.materi.show', [$bab, $materi]) }}"
                            class="group block p-4 sm:p-5 rounded-2xl border transition-all duration-200 bg-white border-border hover:border-green-700 hover:shadow-[0_2px_8px_rgba(22,101,52,0.08)]">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0
                                        {{ $materi->tipe === 'pdf' ? 'bg-red-50 text-red-700' : 'bg-red-50 text-red-600' }}">
                                        @if ($materi->tipe === 'pdf')
                                            <x-ikon nama="file-text" ukuran="24" />
                                        @else
                                            <x-ikon nama="video" ukuran="24" />
                                        @endif
                                    </div>

                                    <div class="min-w-0 space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-muted uppercase">
                                                Materi {{ $index + 1 }} &bull; {{ $materi->tipe === 'pdf' ? 'Dokumen PDF' : 'Video YouTube' }}
                                            </span>
                                        </div>
                                        <h3 class="text-base font-extrabold text-ink truncate group-hover:text-green-700 transition-colors">
                                            {{ $materi->judul }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    @if ($sudahDibuka)
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full">
                                            <x-ikon nama="check" ukuran="12" />
                                            <span>Sudah dipelajari</span>
                                        </span>
                                    @endif
                                    <span class="text-muted group-hover:text-green-700 transition-transform group-hover:translate-x-1">
                                        <x-ikon nama="chevron-right" ukuran="18" />
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Bagian Kuis Bab (Bila Ada) -->
        @if ($kuis)
            <div class="pt-4 border-t border-border">
                <x-kartu class="p-6 bg-surface/60 border-border">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-muted uppercase">Evaluasi Pemahaman</span>
                            <h3 class="text-lg font-extrabold text-ink">{{ $kuis->judul }}</h3>
                            <p class="text-xs text-muted">
                                Kuis bab diselesaikan setelah Anda mempelajari seluruh materi di atas.
                            </p>
                        </div>
                        <div>
                            <x-tombol tipe="utama" href="{{ route('siswa.kuis.show', $kuis) }}" class="!min-h-[40px] !px-5 text-xs font-bold">
                                <span>Buka Kuis Bab</span>
                                <x-ikon nama="arrow-right" ukuran="16" />
                            </x-tombol>
                        </div>
                    </div>
                </x-kartu>
            </div>
        @endif
    </div>
</x-layouts.siswa>
