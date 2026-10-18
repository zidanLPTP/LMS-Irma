<x-layouts.siswa :judul="__('notifikasi.judul_halaman')">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">{{ __('notifikasi.judul_halaman') }}</h1>
                <p class="text-sm text-muted mt-1">{{ __('notifikasi.subjudul_halaman') }}</p>
            </div>

            @if ($daftarNotifikasi->isNotEmpty())
                <form method="POST" action="{{ route('siswa.notifikasi.baca-semua') }}">
                    @csrf
                    <x-tombol tipe="garis" type="submit" class="!min-h-[36px] !px-3 text-xs">
                        <x-ikon nama="check-check" ukuran="16" />
                        <span>{{ __('notifikasi.tombol_tandai_semua') }}</span>
                    </x-tombol>
                </form>
            @endif
        </div>

        @if ($daftarNotifikasi->isEmpty())
            <x-kartu class="p-8 text-center text-muted text-sm">
                {{ __('notifikasi.belum_ada') }}
            </x-kartu>
        @else
            <div class="space-y-3">
                @foreach ($daftarNotifikasi as $notif)
                    @php
                        $isBelumDibaca = is_null($notif->dibaca_pada);
                    @endphp
                    <a href="{{ route('siswa.notifikasi.buka', $notif) }}"
                        class="block p-4 sm:p-5 rounded-2xl border transition-all duration-200 {{ $isBelumDibaca ? 'bg-white border-green-600 shadow-sm' : 'bg-white border-border' }}">
                        <div class="flex items-start gap-4">
                            <div class="p-2.5 rounded-xl shrink-0 {{ $isBelumDibaca ? 'bg-green-100 text-green-700' : 'bg-border text-muted' }}">
                                <x-ikon nama="bell" ukuran="20" />
                            </div>

                            <div class="space-y-1 flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-ink {{ $isBelumDibaca ? 'text-green-950 font-black' : '' }}">
                                        {{ $notif->judul }}
                                    </h2>
                                    <span class="text-xs font-bold text-ink shrink-0">
                                        {{ $notif->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="text-xs text-muted leading-relaxed">
                                    {{ $notif->isi }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pt-4">
                {{ $daftarNotifikasi->links() }}
            </div>
        @endif
    </div>
</x-layouts.siswa>
