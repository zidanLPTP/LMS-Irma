<x-layouts.admin :judul="'Pratinjau Impor Soal — ' . $kuis->judul">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.impor-soal.create', $kuis) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline mb-1">
                    <x-ikon nama="chevron-left" ukuran="16" />
                    <span>Kembali ke Unggah Berkas</span>
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Pratinjau Impor Soal Kuis</h1>
                <p class="text-sm text-muted mt-0.5">
                    Kuis: <strong>{{ $kuis->judul }}</strong> &bull; Berkas: <strong>{{ $hasil['nama_berkas'] }}</strong>
                </p>
            </div>

            <div>
                @if (empty($hasil['baris_galat']) && ! empty($hasil['baris_valid']))
                    <form method="POST" action="{{ route('admin.impor-soal.konfirmasi', $kuis) }}">
                        @csrf
                        <x-tombol tipe="utama" type="submit" class="!px-6">
                            <x-ikon nama="check" ukuran="16" />
                            <span>Konfirmasi Impor ({{ count($hasil['baris_valid']) }} Soal Siap Disimpan)</span>
                        </x-tombol>
                    </form>
                @endif
            </div>
        </div>

        @if ($sudahAdaPercobaan)
            <x-pita-info tipe="peringatan">
                <strong>Peringatan Percobaan Aktif:</strong> Kuis ini sudah pernah dikerjakan oleh siswa. Penambahan soal baru akan ditambahkan di akhir urutan kuis dan tidak mengubah nilai dari percobaan lama.
            </x-pita-info>
        @endif

        @if (! empty($hasil['baris_galat']))
            <x-pita-info tipe="galat">
                <strong>Impor Tidak Dapat Dilakukan!</strong> Terdapat {{ count($hasil['baris_galat']) }} baris bermasalah. Karena impor bersifat atomik (semua atau tidak sama sekali), perbaiki seluruh baris yang bermasalah pada berkas Anda sebelum mengunggah kembali.
            </x-pita-info>

            <x-kartu class="p-6 border border-rose-200 bg-rose-50/20 space-y-4">
                <div class="flex items-center gap-2 text-rose-800 font-extrabold text-sm">
                    <x-ikon nama="alert-circle" ukuran="20" class="text-rose-600" />
                    <span>Daftar Baris Soal yang Bermasalah</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-rose-200 text-rose-900 font-bold">
                                <th class="py-2 px-3">Baris</th>
                                <th class="py-2 px-3">Tipe</th>
                                <th class="py-2 px-3">Pertanyaan</th>
                                <th class="py-2 px-3">Alasan Kesalahan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100">
                            @foreach ($hasil['baris_galat'] as $g)
                                <tr>
                                    <td class="py-2 px-3 font-bold">{{ $g['nomor_baris'] }}</td>
                                    <td class="py-2 px-3">{{ $g['tipe'] ?: '-' }}</td>
                                    <td class="py-2 px-3 max-w-xs truncate">{{ $g['pertanyaan'] ?: '-' }}</td>
                                    <td class="py-2 px-3 text-rose-700 font-bold">{{ $g['alasan'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        @else
            <x-kartu class="p-6 space-y-4">
                <h2 class="text-base font-extrabold text-ink">Seluruh Baris Soal Valid ({{ count($hasil['baris_valid']) }} Butir Soal)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-border text-muted font-bold uppercase">
                                <th class="py-2.5 px-3">No</th>
                                <th class="py-2.5 px-3">Tipe</th>
                                <th class="py-2.5 px-3">Pertanyaan</th>
                                <th class="py-2.5 px-3">Bobot</th>
                                <th class="py-2.5 px-3">Kunci Jawaban</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($hasil['baris_valid'] as $v)
                                <tr>
                                    <td class="py-2.5 px-3 font-bold">{{ $v['nomor_baris'] }}</td>
                                    <td class="py-2.5 px-3 font-bold text-green-700">{{ $v['tipe'] }}</td>
                                    <td class="py-2.5 px-3 max-w-md font-semibold text-ink">{{ $v['pertanyaan'] }}</td>
                                    <td class="py-2.5 px-3 font-bold">{{ $v['bobot'] }}</td>
                                    <td class="py-2.5 px-3">
                                        @if ($v['tipe'] === 'pilihan_ganda')
                                            <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 font-bold">Opsi {{ $v['kunci_pg'] }}</span>
                                        @elseif ($v['tipe'] === 'isian')
                                            <span class="font-mono text-ink">"{{ implode('", "', $v['kunci_isian']) }}"</span>
                                        @else
                                            <span class="text-muted italic">Penilaian Manual</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        @endif
    </div>
</x-layouts.admin>
