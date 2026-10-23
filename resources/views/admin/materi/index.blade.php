<x-layouts.admin judul="Kelola Materi: {{ $bab->judul }}">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.bab.index') }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                    &larr; Kembali ke Daftar Bab
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Materi: {{ $bab->judul }}</h1>
                <p class="text-sm text-muted mt-1">Kelola slide PDF (privat) dan tautan video YouTube untuk bab ini.</p>
            </div>
            <x-tombol tipe="utama" href="{{ route('admin.bab.materi.create', $bab) }}" class="w-full sm:w-auto">
                <x-ikon nama="plus" ukuran="18" />
                <span>Tambah Materi Baru</span>
            </x-tombol>
        </div>

        <x-kartu class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-green-100 text-ink font-bold border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-center w-16">Urutan</th>
                            <th class="px-4 py-3">Judul Materi</th>
                            <th class="px-4 py-3 text-center">Tipe Konten</th>
                            <th class="px-4 py-3">Sumber Berkas / Tautan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($daftarMateri as $materi)
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="px-4 py-3 text-center font-bold">
                                    <div class="flex items-center justify-center gap-1">
                                        <span>{{ $materi->urutan }}</span>
                                        <div class="flex flex-col">
                                            @if ($materi->urutan > 1)
                                                <form method="POST" action="{{ route('admin.bab.materi.naik', [$bab, $materi]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-0.5 text-muted hover:text-green-700" title="Naikkan Urutan">
                                                        <x-ikon nama="chevron-up" ukuran="14" />
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($materi->urutan < $daftarMateri->count())
                                                <form method="POST" action="{{ route('admin.bab.materi.turun', [$bab, $materi]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-0.5 text-muted hover:text-green-700" title="Turunkan Urutan">
                                                        <x-ikon nama="chevron-down" ukuran="14" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-ink">
                                    {{ $materi->judul }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($materi->tipe === 'pdf')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-success border border-green-300">
                                            Slide PDF
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-700 border border-sky-300">
                                            Video YouTube
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-muted">
                                    @if ($materi->tipe === 'pdf')
                                        <span class="font-mono">{{ basename($materi->berkas_pdf) }}</span>
                                        <span class="text-[10px] text-muted block">(Storage Privat)</span>
                                    @else
                                        <a href="{{ $materi->url_youtube }}" target="_blank" rel="noopener" class="text-sky-700 hover:underline font-mono">
                                            {{ Str::limit($materi->url_youtube, 40) }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-tombol tipe="garis" href="{{ route('admin.pratinjau.materi', [$bab, $materi]) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs text-amber-800 border-amber-300 hover:bg-amber-50">
                                            <x-ikon nama="eye" ukuran="14" />
                                            <span>Pratinjau</span>
                                        </x-tombol>
                                        <x-tombol tipe="garis" href="{{ route('admin.bab.materi.edit', [$bab, $materi]) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                            <x-ikon nama="pencil" ukuran="14" />
                                            <span>Ubah</span>
                                        </x-tombol>
                                        <form method="POST" action="{{ route('admin.bab.materi.destroy', [$bab, $materi]) }}" data-konfirmasi="Apakah kamu yakin ingin menghapus materi ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-tombol tipe="bahaya" class="!min-h-[32px] !px-2.5 !py-1 text-xs" type="submit">
                                                <x-ikon nama="trash-2" ukuran="14" />
                                                <span>Hapus</span>
                                            </x-tombol>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-muted">
                                    Belum ada materi untuk bab ini. Silakan tambahkan materi PDF atau video YouTube.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
