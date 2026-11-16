<x-layouts.admin judul="Tambah Tautan Edukasi">
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <x-tombol tipe="garis" href="{{ route('admin.tautan-edukasi.index') }}" class="!p-2 text-muted">
                <x-ikon nama="arrow-left" ukuran="18" />
            </x-tombol>
            <div>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">Tambah Tautan Game Edukasi</h1>
                <p class="text-sm text-muted">Tambahkan link game edukasi luar, simulasi interaktif, atau materi penting.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.tautan-edukasi.store') }}" class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-5">
            @csrf

            <!-- Judul -->
            <div class="space-y-1.5">
                <label for="judul" class="block text-xs font-bold text-ink">
                    Judul Game / Media <span class="text-red-500">*</span>
                </label>
                <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Game Mitosis & Meiosis Interaktif"
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-green-700 @error('judul') border-red-500 @enderror">
                @error('judul')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- URL -->
            <div class="space-y-1.5">
                <label for="url" class="block text-xs font-bold text-ink">
                    Alamat URL Tautan <span class="text-red-500">*</span>
                </label>
                <input type="url" id="url" name="url" value="{{ old('url') }}" required placeholder="https://wordwall.net/play/... atau https://quizizz.com/..."
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm text-ink font-mono focus:outline-none focus:ring-2 focus:ring-green-700 @error('url') border-red-500 @enderror">
                <p class="text-[11px] text-muted">Pastikan menyertakan <span class="font-mono">https://</span> di awal alamat web.</p>
                @error('url')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Deskripsi Singkat -->
            <div class="space-y-1.5">
                <label for="deskripsi" class="block text-xs font-bold text-ink">
                    Deskripsi / Petunjuk Singkat
                </label>
                <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan secara singkat mengenai game ini atau instruksi untuk siswa..."
                    class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-green-700 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <!-- Urutan -->
                <div class="space-y-1.5">
                    <label for="urutan" class="block text-xs font-bold text-ink">
                        Nomor Urutan Tampilan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="urutan" name="urutan" value="{{ old('urutan', $urutanBerikutnya) }}" min="1" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-green-700 @error('urutan') border-red-500 @enderror">
                    @error('urutan')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center gap-2 pt-6">
                    <label class="relative flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" {{ old('aktif', '1') == '1' ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-border text-green-700 focus:ring-green-700">
                        <span class="text-xs font-bold text-ink">Aktifkan sekarang (siswa dapat melihat)</span>
                    </label>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                <x-tombol tipe="garis" href="{{ route('admin.tautan-edukasi.index') }}">
                    Batal
                </x-tombol>
                <x-tombol tipe="utama">
                    Simpan Tautan
                </x-tombol>
            </div>
        </form>
    </div>
</x-layouts.admin>
