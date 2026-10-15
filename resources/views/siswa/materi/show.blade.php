<x-layouts.siswa judul="{{ $materi->judul }} — {{ $bab->judul }}">
    <div class="space-y-6">
        <!-- Navigasi Atas & Info Bab -->
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('siswa.bab.show', $bab) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline">
                <x-ikon nama="chevron-left" ukuran="16" />
                <span>Kembali ke {{ $bab->judul }}</span>
            </a>

            <span class="text-xs font-bold text-muted uppercase">
                {{ $materi->tipe === 'pdf' ? 'Dokumen PDF' : 'Video YouTube' }}
            </span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                    {{ $bab->judul }} &bull; Materi {{ $materi->urutan }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight mt-1">
                    {{ $materi->judul }}
                </h1>
            </div>

            <!-- Tombol Tandai Selesai (Penanda Belajar Pribadi Siswa) -->
            <div>
                <form method="POST" action="{{ route('siswa.bab.materi.toggle-selesai', [$bab, $materi]) }}">
                    @csrf
                    @if ($selesai ?? false)
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-green-100 text-green-800 border border-green-200 hover:bg-green-200 transition-colors">
                            <x-ikon nama="check-circle" ukuran="16" kelas="text-green-700" />
                            <span>Selesai Dipelajari</span>
                            <span class="text-[10px] text-green-700 font-normal">(Klik untuk batalkan)</span>
                        </button>
                    @else
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white text-ink border border-border hover:bg-surface hover:border-green-700 transition-colors">
                            <x-ikon nama="circle" ukuran="16" class="text-muted" />
                            <span>Tandai Selesai</span>
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- Penampil Konten Materi -->
        @if ($materi->tipe === 'youtube')
            <!-- Penampil YouTube Responsif 16:9 nocookie -->
            <x-kartu class="overflow-hidden bg-black rounded-2xl border border-border shadow-sm">
                <div class="relative w-full pb-[56.25%] h-0">
                    <iframe
                        src="{{ $materi->url_youtube }}"
                        title="{{ $materi->judul }}"
                        loading="lazy"
                        class="absolute top-0 left-0 w-full h-full border-0 rounded-2xl"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </x-kartu>
        @elseif ($materi->tipe === 'pdf')
            <!-- Penampil PDF Menggunakan PDF.js Lokal -->
            <x-kartu id="wadah-penampil-pdf" data-url-pdf="{{ $urlPdfPrivat }}" class="p-4 sm:p-6 bg-white border border-border space-y-4">
                <!-- Bar Kontrol Navigasi PDF -->
                <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-surface rounded-xl border border-border">
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-prev-pdf" disabled
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-border text-ink hover:bg-green-50 disabled:opacity-40 disabled:cursor-not-allowed">
                            <x-ikon nama="chevron-left" ukuran="14" />
                            <span>Sebelumnya</span>
                        </button>
                        <button type="button" id="btn-next-pdf" disabled
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-border text-ink hover:bg-green-50 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span>Berikutnya</span>
                            <x-ikon nama="chevron-right" ukuran="14" />
                        </button>
                    </div>

                    <div id="status-halaman-pdf" class="text-xs font-bold text-ink">
                        Memuat dokumen PDF...
                    </div>
                </div>

                <!-- Wadah Canvas PDF Responsif -->
                <div id="wadah-pdf" class="w-full overflow-x-auto flex justify-center bg-surface/40 p-2 sm:p-4 rounded-xl border border-border min-h-[400px] items-center">
                    <canvas id="canvas-pdf" class="max-w-full rounded-lg shadow-sm border border-border bg-white"></canvas>
                </div>

                <div class="text-center text-xs text-muted">
                    Gunakan tombol di atas untuk beralih halaman modul secara bertahap.
                </div>
            </x-kartu>
        @endif

        <!-- Bilah Navigasi Bawah Antar Materi -->
        <div class="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                @if ($materiSebelumnya)
                    <x-tombol tipe="garis" href="{{ route('siswa.bab.materi.show', [$bab, $materiSebelumnya]) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="chevron-left" ukuran="16" />
                        <span>Materi Sebelumnya</span>
                    </x-tombol>
                @else
                    <x-tombol tipe="garis" href="{{ route('siswa.bab.show', $bab) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="chevron-left" ukuran="16" />
                        <span>Daftar Materi</span>
                    </x-tombol>
                @endif
            </div>

            <div>
                @if ($materiBerikutnya)
                    <x-tombol tipe="utama" href="{{ route('siswa.bab.materi.show', [$bab, $materiBerikutnya]) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <span>Materi Selanjutnya</span>
                        <x-ikon nama="chevron-right" ukuran="16" />
                    </x-tombol>
                @else
                    <!-- Materi Terakhir: Balik ke Bab dan tombol kuis bila ada -->
                    <div class="flex items-center gap-3">
                        <x-tombol tipe="garis" href="{{ route('siswa.bab.show', $bab) }}" class="!min-h-[42px] text-xs font-bold">
                            <span>Selesai &bull; Kembali ke Bab</span>
                        </x-tombol>
                        @if ($bab->kuis)
                            <x-tombol tipe="utama" href="{{ route('siswa.kuis.show', $bab->kuis) }}" class="!min-h-[42px] text-xs font-bold">
                                <span>Lanjut ke Kuis Bab</span>
                                <x-ikon nama="arrow-right" ukuran="16" />
                            </x-tombol>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.siswa>
