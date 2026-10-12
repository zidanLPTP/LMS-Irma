<x-layouts.admin :judul="'Unggah Impor Soal — ' . $kuis->judul">
    <div class="space-y-6 max-w-3xl">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.kuis.soal.index', $kuis) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline mb-1">
                    <x-ikon nama="chevron-left" ukuran="16" />
                    <span>Kembali ke Soal Kuis</span>
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Impor Soal dari Excel</h1>
                <p class="text-sm text-muted mt-1">Kuis: <strong>{{ $kuis->judul }}</strong></p>
            </div>

            <x-tombol tipe="garis" href="{{ route('admin.impor-soal.templat') }}" class="!min-h-[38px] text-xs font-bold shrink-0">
                <x-ikon nama="download" ukuran="16" />
                <span>Unduh Templat Excel</span>
            </x-tombol>
        </div>

        @if ($sudahAdaPercobaan)
            <x-pita-info tipe="peringatan">
                <strong>Perhatian:</strong> Kuis ini sudah memiliki riwayat pengerjaan oleh siswa. Menambahkan butir soal baru melalui impor tidak akan mengubah nilai pada percobaan lama yang sudah selesai.
            </x-pita-info>
        @endif

        <x-kartu class="p-6 space-y-6">
            <div class="p-4 bg-green-50 rounded-2xl border border-green-200 text-xs space-y-2">
                <div class="flex items-center gap-2 font-extrabold text-green-900">
                    <x-ikon nama="info" ukuran="18" class="text-green-700" />
                    <span>Ketentuan Impor Soal:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-green-800">
                    <li>Mendukung tipe: pilihan_ganda, isian, dan esai.</li>
                    <li>Maksimal 100 butir soal per berkas (ukuran maksimal 1 MB).</li>
                    <li>Impor bersifat <strong>SEMUA ATAU TIDAK SAMA SEKALI (atomik)</strong>. Bila ada satu saja baris yang salah format/aturan, impor akan dibatalkan agar urutan soal tetap konsisten.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.impor-soal.pratinjau', $kuis) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="berkas" class="block text-sm font-bold text-ink mb-1">Pilih Berkas Excel Soal (.xlsx)</label>
                    <input type="file" id="berkas" name="berkas" accept=".xlsx" required
                        class="block w-full text-sm text-ink file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 cursor-pointer">
                    @error('berkas')
                        <p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 flex justify-end">
                    <x-tombol tipe="utama" type="submit" class="!px-6">
                        <span>Lanjut ke Pratinjau Soal</span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
