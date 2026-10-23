<x-layouts.admin judul="Kelola Soal: {{ $kuis->judul }}">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.kuis.index') }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                    &larr; Kembali ke Daftar Kuis
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Butir Soal: {{ $kuis->judul }}</h1>
                <p class="text-sm text-muted mt-1">Kelola bank butir soal pilihan ganda, isian singkat, dan esai.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-tombol tipe="garis" href="{{ route('admin.pratinjau.kuis', $kuis) }}" class="text-xs font-bold text-amber-800 border-amber-300 hover:bg-amber-50">
                    <x-ikon nama="eye" ukuran="16" />
                    <span>Pratinjau Kuis</span>
                </x-tombol>
                <x-tombol tipe="garis" href="{{ route('admin.impor-soal.create', $kuis) }}" class="text-xs font-bold bg-white text-ink border-border hover:bg-surface">
                    <x-ikon nama="upload" ukuran="16" />
                    <span>Impor Excel</span>
                </x-tombol>
                <x-tombol tipe="utama" href="{{ route('admin.kuis.soal.create', $kuis) }}" class="w-full sm:w-auto text-xs font-bold">
                    <x-ikon nama="plus" ukuran="16" />
                    <span>Tambah Butir Soal</span>
                </x-tombol>
            </div>
        </div>

        <x-pita-info tipe="info">
            <strong>Catatan Aturan Soal:</strong> Urutan soal pilihan ganda dan isian dapat diacak oleh sistem. Soal bertipe <strong>esai akan otomatis selalu ditempatkan di nomor-nomor terakhir</strong> saat siswa mengerjakan kuis.
        </x-pita-info>

        <x-kartu class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-green-100 text-ink font-bold border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-center w-16">Urutan</th>
                            <th class="px-4 py-3">Pertanyaan</th>
                            <th class="px-4 py-3 text-center">Tipe</th>
                            <th class="px-4 py-3 text-center">Bobot</th>
                            <th class="px-4 py-3">Kunci Jawaban</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($daftarSoal as $soal)
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="px-4 py-3 text-center font-bold">
                                    <div class="flex items-center justify-center gap-1">
                                        <span>{{ $soal->urutan }}</span>
                                        <div class="flex flex-col">
                                            @if ($soal->urutan > 1)
                                                <form method="POST" action="{{ route('admin.kuis.soal.naik', [$kuis, $soal]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-0.5 text-muted hover:text-green-700" title="Naikkan Urutan">
                                                        <x-ikon nama="chevron-up" ukuran="14" />
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($soal->urutan < $daftarSoal->count())
                                                <form method="POST" action="{{ route('admin.kuis.soal.turun', [$kuis, $soal]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-0.5 text-muted hover:text-green-700" title="Turunkan Urutan">
                                                        <x-ikon nama="chevron-down" ukuran="14" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-ink max-w-xs">
                                    <p class="line-clamp-2">{{ $soal->pertanyaan }}</p>
                                    @if ($soal->jawaban_siswa_count > 0)
                                        <span class="inline-block text-[10px] bg-[#FEF3C7] text-warning border border-[#FDE68A] font-bold px-1.5 py-0.5 rounded mt-1">
                                            Terkunci Penelitian ({{ $soal->jawaban_siswa_count }} jawaban)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($soal->tipe_soal === 'pilihan_ganda')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-success border border-green-300">
                                            PG
                                        </span>
                                    @elseif ($soal->tipe_soal === 'isian')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-700 border border-sky-300">
                                            Isian
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#FEF3C7] text-warning border border-[#FDE68A]">
                                            Esai (Di Akhir)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-ink">
                                    {{ $soal->bobot_poin }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted max-w-xs">
                                    @if ($soal->tipe_soal === 'pilihan_ganda')
                                        @php
                                            $benar = $soal->pilihanJawaban->firstWhere('apakah_benar', true);
                                        @endphp
                                        <span class="text-success font-semibold">Kunci: {{ $benar ? $benar->teks_pilihan : '-' }}</span>
                                    @elseif ($soal->tipe_soal === 'isian')
                                        <span class="text-sky-700 font-semibold">Kunci: {{ $soal->pilihanJawaban->pluck('teks_pilihan')->join(', ') }}</span>
                                    @else
                                        <span class="text-muted italic">Dinilai manual oleh admin</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-tombol tipe="garis" href="{{ route('admin.kuis.soal.edit', [$kuis, $soal]) }}" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                            <x-ikon nama="pencil" ukuran="14" />
                                            <span>Ubah</span>
                                        </x-tombol>
                                        <form method="POST" action="{{ route('admin.kuis.soal.destroy', [$kuis, $soal]) }}" data-konfirmasi="Apakah kamu yakin ingin menghapus butir soal ini?" data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Hapus" class="inline">
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
                                    Belum ada butir soal di kuis ini. Silakan buat soal pilihan ganda, isian, atau esai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
