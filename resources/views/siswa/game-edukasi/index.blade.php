<x-layouts.siswa judul="Game Edukasi & Media">
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200/80 p-6 sm:p-8 space-y-3">
            <div class="absolute -right-6 -bottom-8 w-40 h-40 pointer-events-none select-none opacity-[0.12] text-green-700 hidden sm:block">
                <img src="{{ asset('gambar/ornamen/ornamen-mikroskop.svg') }}" alt="" class="w-full h-full">
            </div>
            <div class="relative z-10 space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-white text-green-700 border border-green-200 shadow-xs">
                    <x-ikon nama="gamepad-2" ukuran="15" />
                    Media Interaktif Pembelajaran
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                    Game Edukasi & Media Belajar
                </h1>
                <p class="text-sm text-muted max-w-2xl leading-relaxed">
                    Asah pemahaman biologi Anda melalui berbagai simulasi interaktif, kuis game edukatif, dan aktivitas menarik berikut. Klik tombol untuk memainkan langsung di peramban Anda!
                </p>
            </div>
        </div>

        @if ($tautan->isEmpty())
            <x-kartu class="p-10 text-center space-y-3">
                <div class="w-14 h-14 rounded-full bg-green-50 text-green-700 flex items-center justify-center mx-auto">
                    <x-ikon nama="gamepad-2" ukuran="28" />
                </div>
                <h3 class="text-base font-bold text-ink">Belum Ada Game Edukasi yang Tersedia</h3>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Guru atau instruktur Anda belum menerbitkan tautan game edukasi saat ini. Silakan periksa kembali nanti atau lanjutkan mempelajari materi bab.
                </p>
                <div class="pt-2">
                    <x-tombol tipe="garis" href="{{ route('siswa.beranda') }}" class="text-xs">
                        Kembali ke Beranda
                    </x-tombol>
                </div>
            </x-kartu>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($tautan as $item)
                    <div class="bg-white rounded-2xl border border-border p-5 flex flex-col justify-between hover:border-green-600/50 hover:shadow-md transition-all group">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <x-ikon nama="gamepad-2" ukuran="22" />
                                </div>
                                <span class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-surface text-muted">
                                    #{{ $item->urutan }}
                                </span>
                            </div>

                            <div>
                                <h3 class="font-extrabold text-ink text-base group-hover:text-green-700 transition-colors">
                                    {{ $item->judul }}
                                </h3>
                                @if ($item->deskripsi)
                                    <p class="text-xs text-muted mt-1.5 leading-relaxed">
                                        {{ $item->deskripsi }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="pt-5 mt-4 border-t border-border flex items-center justify-between gap-3">
                            <span class="text-[11px] text-muted font-medium flex items-center gap-1 truncate max-w-[150px]">
                                <x-ikon nama="link" ukuran="12" class="shrink-0" />
                                <span class="truncate">{{ parse_url($item->url, PHP_URL_HOST) ?? 'Tautan Luar' }}</span>
                            </span>

                            <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-extrabold bg-green-700 text-white hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2 transition-all shadow-xs">
                                <span>Mainkan Game</span>
                                <x-ikon nama="external-link" ukuran="14" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.siswa>
