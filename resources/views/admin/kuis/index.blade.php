<x-layouts.admin judul="Kelola Kuis dan Soal">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">Kuis dan Bank Soal</h1>
                <p class="text-sm text-muted mt-1">Kelola Pretest, Kuis Bab, Posttest, dan butir-butir soal.</p>
            </div>
            <x-tombol tipe="utama" href="{{ route('admin.kuis.create') }}" class="w-full sm:w-auto">
                <x-ikon nama="plus" ukuran="18" />
                <span>Buat Kuis Baru</span>
            </x-tombol>
        </div>

        <x-kartu class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-green-100 text-ink font-bold border-b border-border">
                        <tr>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Judul Kuis</th>
                            <th class="px-4 py-3">Terkait Bab</th>
                            <th class="px-4 py-3 text-center">Batas Waktu</th>
                            <th class="px-4 py-3 text-center">KKM</th>
                            <th class="px-4 py-3 text-center">Maks. Kesempatan</th>
                            <th class="px-4 py-3 text-center">Jumlah Soal</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($daftarKuis as $kuis)
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="px-4 py-3">
                                    @if ($kuis->tipe_kuis === 'pretest')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#FEF3C7] text-warning border border-[#FDE68A]">
                                            Pretest
                                        </span>
                                    @elseif ($kuis->tipe_kuis === 'posttest')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-700 border border-sky-300">
                                            Posttest
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-success border border-green-300">
                                            Kuis Bab
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-ink">
                                    <a href="{{ route('admin.kuis.soal.index', $kuis) }}" class="hover:text-green-700 hover:underline">
                                        {{ $kuis->judul }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-muted">
                                    {{ $kuis->bab ? $kuis->bab->judul : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    {{ $kuis->batasWaktuEfektif() }}s
                                    @if (is_null($kuis->batas_waktu_per_soal))
                                        <span class="text-[10px] text-muted block">(bawaan)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    {{ $kuis->nilaiLulusEfektif() }}
                                    @if (is_null($kuis->nilai_lulus))
                                        <span class="text-[10px] text-muted block">(bawaan)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    {{ $kuis->maksimalKesempatanEfektif() }}x
                                    @if (is_null($kuis->maksimal_kesempatan) && $kuis->tipe_kuis === 'kuis_bab')
                                        <span class="text-[10px] text-muted block">(bawaan)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.kuis.soal.index', $kuis) }}" class="font-bold text-green-700 hover:underline">
                                        {{ $kuis->soal_count }} Butir
                                    </a>
                                    @if ($kuis->percobaan_count > 0)
                                        <span class="inline-block px-1.5 py-0.5 mt-1 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                            {{ $kuis->percobaan_count }} Dikerjakan
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <x-tombol tipe="utama" href="{{ route('admin.kuis.soal.index', $kuis) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                            <x-ikon nama="clipboard-list" ukuran="14" />
                                            <span>Kelola Soal</span>
                                        </x-tombol>
                                        <x-tombol tipe="garis" href="{{ route('admin.pratinjau.kuis', $kuis) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs text-amber-800 border-amber-300 hover:bg-amber-50">
                                            <x-ikon nama="eye" ukuran="14" />
                                            <span>Pratinjau</span>
                                        </x-tombol>
                                        <x-tombol tipe="garis" href="{{ route('admin.kuis.edit', $kuis) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                            <x-ikon nama="pencil" ukuran="14" />
                                            <span>Ubah</span>
                                        </x-tombol>
                                        @if ($kuis->percobaan_count > 0)
                                            <form method="POST" action="{{ route('admin.kuis.reset-pengerjaan', $kuis) }}" data-konfirmasi="PENTING: Kuis ini memiliki {{ $kuis->percobaan_count }} riwayat pengerjaan siswa. Apakah Anda yakin ingin MERESET (menghapus semua jawaban & percobaan) kuis ini? Tindakan ini akan mengosongkan riwayat pengerjaan agar kuis/soal bisa diedit atau dihapus kembali." data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Reset Pengerjaan" class="inline">
                                                @csrf
                                                <x-tombol tipe="bahaya" class="!min-h-[32px] !px-2.5 !py-1 text-xs !bg-amber-600 !border-amber-600 hover:!bg-amber-700" type="submit" title="Reset semua pengerjaan siswa agar kuis/soal bisa diedit/dihapus">
                                                    <x-ikon nama="rotate-ccw" ukuran="14" />
                                                    <span>Reset</span>
                                                </x-tombol>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.kuis.destroy', $kuis) }}" data-konfirmasi="Apakah Anda yakin ingin menghapus kuis ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus" class="inline">
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
                                <td colspan="8" class="px-4 py-8 text-center text-muted">
                                    Belum ada kuis yang dibuat. Silakan buat Pretest, Kuis Bab, atau Posttest.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
