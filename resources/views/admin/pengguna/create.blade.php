<x-layouts.admin judul="Tambah Siswa Manual">
    <div class="max-w-xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pengguna.index') }}" class="p-2 rounded-xl border border-border bg-white text-muted hover:text-ink">
                <x-ikon nama="arrow-left" ukuran="18" />
            </a>
            <div>
                <h1 class="text-2xl font-black text-ink">Tambah Siswa Baru</h1>
                <p class="text-sm text-muted">Akun dibuat langsung aktif dan wajib mengganti kata sandi pada login pertama.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.pengguna.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="nama_lengkap" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Nama Lengkap Siswa</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    @error('nama_lengkap')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Username Akun</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    <p class="text-[11px] text-muted mt-1">Hanya huruf, angka, dan garis bawah/setrip tanpa spasi.</p>
                    @error('username')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    @error('email')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kelas" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Kelas (Opsional)</label>
                    <input type="text" id="kelas" name="kelas" value="{{ old('kelas') }}" placeholder="Contoh: XI MIPA 1" class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    @error('kelas')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                    <a href="{{ route('admin.pengguna.index') }}" class="px-4 py-2.5 text-sm font-bold text-muted hover:text-ink">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2.5 bg-green-700 text-white rounded-xl text-sm font-bold hover:bg-green-800 shadow-sm transition-colors">
                        Simpan Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
