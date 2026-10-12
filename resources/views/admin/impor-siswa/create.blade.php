<x-layouts.admin judul="Unggah Impor Siswa">
    <div class="space-y-6 max-w-3xl">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline mb-1">
                    <x-ikon nama="chevron-left" ukuran="16" />
                    <span>Kembali ke Kelola Pengguna</span>
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Impor Siswa dari Excel</h1>
                <p class="text-sm text-muted mt-1">Unggah berkas spreadsheet .xlsx untuk menambahkan akun siswa secara massal.</p>
            </div>

            <x-tombol tipe="garis" href="{{ route('admin.impor-siswa.templat') }}" class="!min-h-[38px] text-xs font-bold shrink-0">
                <x-ikon nama="download" ukuran="16" />
                <span>Unduh Templat Excel</span>
            </x-tombol>
        </div>

        <x-kartu class="p-6 space-y-6">
            <div class="p-4 bg-green-50 rounded-2xl border border-green-200 text-xs space-y-2">
                <div class="flex items-center gap-2 font-extrabold text-green-900">
                    <x-ikon nama="info" ukuran="18" class="text-green-700" />
                    <span>Petunjuk Pengisian:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-green-800">
                    <li>Gunakan templat Excel yang disediakan agar nama kolom sesuai.</li>
                    <li>Maksimal 200 baris data siswa dan ukuran berkas maksimal 1 MB.</li>
                    <li>Sistem akan melakukan pratinjau validasi terlebih dahulu sebelum menyimpan data.</li>
                    <li>Kata sandi sementara akan dibuatkan otomatis dan dapat diunduh sekali setelah konfirmasi.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.impor-siswa.pratinjau') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="berkas" class="block text-sm font-bold text-ink mb-1">Pilih Berkas Excel (.xlsx)</label>
                    <input type="file" id="berkas" name="berkas" accept=".xlsx" required
                        class="block w-full text-sm text-ink file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 cursor-pointer">
                    @error('berkas')
                        <p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 flex justify-end">
                    <x-tombol tipe="utama" type="submit" class="!px-6">
                        <span>Lanjut ke Pratinjau Validasi</span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
