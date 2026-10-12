<x-layouts.siswa :judul="__('pengumuman.judul_halaman')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('pengumuman.judul_halaman') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('pengumuman.subjudul_halaman') }}</p>
        </div>

        @if ($daftarPengumuman->isEmpty())
            <x-kartu class="p-8 text-center text-muted text-sm">
                {{ __('pengumuman.belum_ada') }}
            </x-kartu>
        @else
            <div class="space-y-4">
                @foreach ($daftarPengumuman as $pengumuman)
                    @php
                        $sudahDibaca = in_array($pengumuman->id, $dibacaIds);
                    @endphp
                    <x-kartu class="p-5 border border-border">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($pengumuman->disematkan)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-800">
                                            <x-ikon nama="pin" ukuran="12" />
                                            {{ __('pengumuman.lencana_disematkan') }}
                                        </span>
                                    @endif

                                    @if (! $sudahDibaca)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-700">
                                            {{ __('pengumuman.lencana_baru') }}
                                        </span>
                                    @endif

                                    <span class="text-xs text-muted">
                                        {{ $pengumuman->created_at->translatedFormat('d M Y, H:i') }}
                                    </span>
                                </div>

                                <h2 class="text-lg font-extrabold text-ink">
                                    <a href="{{ route('siswa.pengumuman.show', $pengumuman) }}" class="hover:text-green-700">
                                        {{ $pengumuman->judul }}
                                    </a>
                                </h2>

                                <p class="text-xs text-muted whitespace-pre-line line-clamp-3">
                                    {{ $pengumuman->isi }}
                                </p>
                            </div>

                            <div class="shrink-0 self-start sm:self-center">
                                <x-tombol tipe="garis" href="{{ route('siswa.pengumuman.show', $pengumuman) }}" class="!min-h-[38px] !px-4 text-xs font-bold">
                                    Baca Selengkapnya
                                </x-tombol>
                            </div>
                        </div>
                    </x-kartu>
                @endforeach
            </div>

            <div class="pt-4">
                {{ $daftarPengumuman->links() }}
            </div>
        @endif
    </div>
</x-layouts.siswa>
