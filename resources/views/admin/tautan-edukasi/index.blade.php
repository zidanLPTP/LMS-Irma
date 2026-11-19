<x-layouts.admin judul="Kelola Tautan Edukasi">
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">Tautan Game Edukasi & Media</h1>
                <p class="text-sm text-muted">Kelola tautan game edukasi interaktif, simulasi, atau media pendukung pembelajaran untuk siswa.</p>
            </div>
            <x-tombol tipe="utama" href="{{ route('admin.tautan-edukasi.create') }}" class="gap-2 shrink-0">
                <x-ikon nama="plus" ukuran="18" />
                <span>Tambah Tautan</span>
            </x-tombol>
        </div>

        @if (session('status'))
            <x-pita-info tipe="sukses">
                {{ session('status') }}
            </x-pita-info>
        @endif

        <!-- Daftar Tautan -->
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            @if ($tautan->isEmpty())
                <div class="p-10 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-green-50 text-green-700 flex items-center justify-center mx-auto">
                        <x-ikon nama="gamepad-2" ukuran="24" />
                    </div>
                    <h3 class="text-base font-bold text-ink">Belum Ada Tautan Game Edukasi</h3>
                    <p class="text-xs text-muted max-w-sm mx-auto">Tambahkan link game edukasi luar, simulasi virtual lab, atau media interaktif yang dapat dimainkan siswa.</p>
                    <x-tombol tipe="garis" href="{{ route('admin.tautan-edukasi.create') }}" class="mt-2 text-xs">
                        Tambah Sekarang
                    </x-tombol>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-surface border-b border-border text-muted font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">Urutan</th>
                                <th class="px-4 py-3">Judul & Deskripsi</th>
                                <th class="px-4 py-3">Alamat URL</th>
                                <th class="px-4 py-3 w-28 text-center">Status</th>
                                <th class="px-4 py-3 w-36 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($tautan as $item)
                                <tr class="hover:bg-green-50/50 transition-colors">
                                    <td class="px-4 py-3 text-center font-bold text-muted">
                                        {{ $item->urutan }}
                                    </td>
                                    <td class="px-4 py-3 space-y-1">
                                        <div class="font-extrabold text-ink text-sm flex items-center gap-2">
                                            <x-ikon nama="gamepad-2" ukuran="16" class="text-green-700 shrink-0" />
                                            <span>{{ $item->judul }}</span>
                                        </div>
                                        @if ($item->deskripsi)
                                            <p class="text-muted text-[11px] line-clamp-2 max-w-md">{{ $item->deskripsi }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="text-green-700 hover:underline font-mono text-[11px] flex items-center gap-1 max-w-xs truncate">
                                            <span class="truncate">{{ $item->url }}</span>
                                            <x-ikon nama="external-link" ukuran="12" class="shrink-0" />
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form method="POST" action="{{ route('admin.tautan-edukasi.toggle', $item) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition-colors {{ $item->aktif ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $item->aktif ? 'bg-green-600' : 'bg-gray-400' }}"></span>
                                                {{ $item->aktif ? 'Aktif' : 'Non-aktif' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-tombol tipe="garis" href="{{ route('admin.tautan-edukasi.edit', $item) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                                <x-ikon nama="pencil" ukuran="14" />
                                                <span>Ubah</span>
                                            </x-tombol>

                                            <form method="POST" action="{{ route('admin.tautan-edukasi.destroy', $item) }}" data-konfirmasi="Apakah kamu yakin ingin menghapus tautan game edukasi ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus" class="inline">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
