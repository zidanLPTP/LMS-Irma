<x-layouts.admin judul="Kelola Bab dan Materi">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">Bab dan Materi Pembelajaran</h1>
                <p class="text-sm text-muted mt-1">Kelola urutan bab dinamis, status terbit, dan konten slide/video.</p>
            </div>
            <x-tombol tipe="utama" href="{{ route('admin.bab.create') }}" class="w-full sm:w-auto">
                <x-ikon nama="plus" ukuran="18" />
                <span>Tambah Bab Baru</span>
            </x-tombol>
        </div>

        <x-kartu class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-green-100 text-ink font-bold border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-center w-16">Urutan</th>
                            <th class="px-4 py-3">Judul Bab</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Materi</th>
                            <th class="px-4 py-3 text-center">Kuis Bab</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($daftarBab as $bab)
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="px-4 py-3 text-center font-bold">
                                    <div class="flex items-center justify-center gap-1">
                                        <span>{{ $bab->urutan }}</span>
                                        <div class="flex flex-col">
                                            @if ($bab->urutan > 1)
                                                <form method="POST" action="{{ route('admin.bab.naik', $bab) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-0.5 text-muted hover:text-green-700" title="Naikkan Urutan">
                                                        <x-ikon nama="chevron-up" ukuran="14" />
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($bab->urutan < $daftarBab->count())
                                                <form method="POST" action="{{ route('admin.bab.turun', $bab) }}" class="inline">
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
                                    {{ $bab->judul }}
                                    @if ($bab->deskripsi)
                                        <p class="text-xs text-muted font-normal mt-0.5 line-clamp-1">{{ $bab->deskripsi }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('admin.bab.terbit', $bab) }}" class="inline">
                                        @csrf
                                        @if ($bab->status_terbit)
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-success border border-green-300 hover:opacity-80" title="Klik untuk sembunyikan">
                                                <x-ikon nama="eye" ukuran="14" />
                                                <span>Terbit</span>
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-muted border border-border hover:opacity-80" title="Klik untuk terbitkan">
                                                <x-ikon nama="eye-off" ukuran="14" />
                                                <span>Draf</span>
                                            </button>
                                        @endif
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.bab.materi.index', $bab) }}" class="font-bold text-sky-700 hover:underline">
                                        {{ $bab->materi_count }} Materi
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($bab->kuis)
                                        <a href="{{ route('admin.kuis.soal.index', $bab->kuis) }}" class="font-bold text-green-700 hover:underline">
                                            Ada ({{ $bab->kuis->judul }})
                                        </a>
                                    @else
                                        <span class="text-muted text-xs">Tanpa Kuis</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-tombol tipe="garis" href="{{ route('admin.pratinjau.bab', $bab) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs text-amber-800 border-amber-300 hover:bg-amber-50">
                                            <x-ikon nama="eye" ukuran="14" />
                                            <span>Pratinjau</span>
                                        </x-tombol>
                                        <x-tombol tipe="garis" href="{{ route('admin.bab.edit', $bab) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                            <x-ikon nama="pencil" ukuran="14" />
                                            <span>Ubah</span>
                                        </x-tombol>
                                        <form method="POST" action="{{ route('admin.bab.destroy', $bab) }}" data-konfirmasi="Apakah kamu yakin ingin menghapus bab ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus" class="inline">
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
                                <td colspan="6" class="px-4 py-8 text-center text-muted">
                                    Belum ada bab yang dibuat. Silakan tambahkan bab baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
