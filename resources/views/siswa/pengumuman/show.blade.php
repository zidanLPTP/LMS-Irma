<x-layouts.siswa :judul="$pengumuman->judul">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <a href="{{ route('siswa.pengumuman.index') }}" class="text-xs font-bold text-green-700 hover:underline inline-flex items-center gap-1.5 mb-3">
                <x-ikon nama="arrow-left" ukuran="14" />
                <span>{{ __('pengumuman.kembali') }}</span>
            </a>

            <div class="flex items-center gap-2 flex-wrap mb-2">
                @if ($pengumuman->disematkan)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-800">
                        <x-ikon nama="pin" ukuran="12" />
                        {{ __('pengumuman.lencana_disematkan') }}
                    </span>
                @endif
                <span class="text-xs text-muted">
                    Diterbitkan pada {{ $pengumuman->created_at->translatedFormat('d F Y, H:i') }} WIB
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink leading-tight">
                {{ $pengumuman->judul }}
            </h1>
        </div>

        <x-kartu class="p-6 sm:p-8">
            <!-- Isi Teks Biasa: Baris Baru Tetap Terbaca dan Aman dari XSS -->
            <div class="text-sm text-ink leading-relaxed whitespace-pre-line font-sans">
                {{ $pengumuman->isi }}
            </div>
        </x-kartu>
    </div>
</x-layouts.siswa>
