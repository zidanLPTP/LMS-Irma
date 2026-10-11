<x-layouts.admin judul="Tambah Bab Baru">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">Tambah Bab Pembelajaran</h1>
            <p class="text-sm text-muted mt-1">Buat bab baru untuk penelitian. Urutan bab akan ditempatkan di posisi terakhir.</p>
        </div>

        @if ($errors->any())
            <x-pita-info tipe="galat">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-pita-info>
        @endif

        <x-kartu class="p-6">
            <form method="POST" action="{{ route('admin.bab.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="judul" class="block text-sm font-bold text-ink mb-1">Judul Bab</label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Bab 1: Struktur dan Fungsi Sel"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="deskripsi" class="block text-sm font-bold text-ink mb-1">Deskripsi Singkat <span class="font-normal text-muted">(opsional)</span></label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Ringkasan isi materi yang akan dipelajari siswa..."
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">{{ old('deskripsi') }}</textarea>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-ink font-semibold text-sm">
                        <input type="checkbox" name="status_terbit" value="1" {{ old('status_terbit', true) ? 'checked' : '' }}
                            class="rounded border-border text-green-700 focus:ring-green-700">
                        <span>Terbitkan bab ini agar langsung tampil ke siswa</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-border">
                    <x-tombol tipe="garis" href="{{ route('admin.bab.index') }}">Batal</x-tombol>
                    <x-tombol tipe="utama" type="submit">Simpan Bab</x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
