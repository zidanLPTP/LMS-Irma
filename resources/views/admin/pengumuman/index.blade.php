<x-layouts.admin :judul="__('pengumuman.judul_halaman')">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">{{ __('pengumuman.judul_halaman') }}</h1>
                <p class="text-sm text-muted mt-1">{{ __('pengumuman.subjudul_halaman') }}</p>
            </div>
            <x-tombol tipe="utama" href="{{ route('admin.pengumuman.create') }}" class="!min-h-[40px] text-xs">
                <x-ikon nama="plus" ukuran="16" />
                <span>{{ __('pengumuman.tombol_tambah') }}</span>
            </x-tombol>
        </div>

        <x-kartu class="p-6">
            @if ($daftarPengumuman->isEmpty())
                <p class="text-sm text-muted text-center py-6">{{ __('pengumuman.belum_ada') }}</p>
            @else
                <div class="divide-y divide-border">
                    @foreach ($daftarPengumuman as $pengumuman)
                        <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($pengumuman->disematkan)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-800">
                                            <x-ikon nama="pin" ukuran="12" />
                                            {{ __('pengumuman.lencana_disematkan') }}
                                        </span>
                                    @endif

                                    @if ($pengumuman->status === 'terbit')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-green-100 text-green-800">
                                            {{ __('pengumuman.status_terbit') }}
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface text-muted border border-border">
                                            {{ __('pengumuman.status_draf') }}
                                        </span>
                                    @endif

                                    <span class="text-xs text-muted">
                                        {{ $pengumuman->created_at->translatedFormat('d M Y, H:i') }}
                                    </span>

                                    @if ($pengumuman->tanggal_kedaluwarsa)
                                        <span class="text-xs text-muted">
                                            &bull; Kedaluwarsa: {{ $pengumuman->tanggal_kedaluwarsa->translatedFormat('d M Y') }}
                                        </span>
                                    @endif
                                </div>

                                <h2 class="text-base font-extrabold text-ink">{{ $pengumuman->judul }}</h2>
                                <p class="text-xs text-muted whitespace-pre-line line-clamp-2">{{ $pengumuman->isi }}</p>
                            </div>

                            <div class="flex items-center gap-2 self-start shrink-0">
                                <x-tombol tipe="garis" href="{{ route('admin.pengumuman.edit', $pengumuman) }}" class="!min-h-[36px] !px-3 text-xs">
                                    Ubah
                                </x-tombol>

                                <form method="POST" action="{{ route('admin.pengumuman.destroy', $pengumuman) }}" data-konfirmasi="Apakah Anda yakin ingin menghapus pengumuman ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <x-tombol tipe="bahaya" class="!min-h-[36px] !px-3 text-xs">
                                        Hapus
                                    </x-tombol>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-border">
                    {{ $daftarPengumuman->links() }}
                </div>
            @endif
        </x-kartu>
    </div>
</x-layouts.admin>
