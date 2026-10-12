<x-layouts.siswa :judul="'[Pratinjau] ' . $materi->judul">
    <div class="space-y-6">
        <!-- Banner Mode Pratinjau -->
        <div class="p-3 bg-amber-500 text-white rounded-2xl flex items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2">
                <x-ikon nama="eye" ukuran="20" />
                <span class="text-xs font-bold">Mode Pratinjau Guru/Admin — Tidak ada data, progres, atau log akses yang dicatat.</span>
            </div>
            <a href="{{ route('admin.pratinjau.bab', $bab) }}" class="text-xs font-extrabold underline hover:text-amber-100">
                Kembali ke Bab
            </a>
        </div>

        <!-- Navigasi Atas & Info Bab -->
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('admin.pratinjau.bab', $bab) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline">
                <x-ikon nama="chevron-left" ukuran="16" />
                <span>Kembali ke {{ $bab->judul }}</span>
            </a>

            <span class="text-xs font-bold text-muted uppercase">
                {{ $materi->tipe === 'pdf' ? 'Dokumen PDF' : 'Video YouTube' }}
            </span>
        </div>

        <div>
            <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                {{ $bab->judul }} &bull; Materi {{ $materi->urutan }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight mt-1">
                {{ $materi->judul }}
            </h1>
        </div>

        <!-- Penampil Konten Materi -->
        @if ($materi->tipe === 'youtube')
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
            <x-kartu class="p-4 sm:p-6 bg-white border border-border space-y-4">
                <div class="p-4 bg-surface rounded-xl border border-border text-center">
                    <p class="text-xs text-muted font-bold mb-2">Dokumen PDF Tersemat</p>
                    <a href="{{ $urlPdfPrivat }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-green-100 text-green-800 hover:bg-green-200">
                        <x-ikon nama="file-text" ukuran="16" />
                        <span>Buka PDF di Tab Baru</span>
                    </a>
                </div>
            </x-kartu>
        @endif

        <!-- Bilah Navigasi Bawah Antar Materi -->
        <div class="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                @if ($materiSebelumnya)
                    <x-tombol tipe="garis" href="{{ route('admin.pratinjau.materi', [$bab, $materiSebelumnya]) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="chevron-left" ukuran="16" />
                        <span>Materi Sebelumnya</span>
                    </x-tombol>
                @else
                    <x-tombol tipe="garis" href="{{ route('admin.pratinjau.bab', $bab) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="chevron-left" ukuran="16" />
                        <span>Daftar Materi</span>
                    </x-tombol>
                @endif
            </div>

            <div>
                @if ($materiBerikutnya)
                    <x-tombol tipe="utama" href="{{ route('admin.pratinjau.materi', [$bab, $materiBerikutnya]) }}" class="!min-h-[42px] text-xs font-bold w-full sm:w-auto">
                        <span>Materi Selanjutnya</span>
                        <x-ikon nama="chevron-right" ukuran="16" />
                    </x-tombol>
                @else
                    <x-tombol tipe="garis" href="{{ route('admin.pratinjau.bab', $bab) }}" class="!min-h-[42px] text-xs font-bold">
                        <span>Selesai &bull; Kembali ke Bab</span>
                    </x-tombol>
                @endif
            </div>
        </div>
    </div>
</x-layouts.siswa>
