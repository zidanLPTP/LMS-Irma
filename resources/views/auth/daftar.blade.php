<x-layouts.induk judul="Daftar Akun Siswa">
    <div class="max-w-md mx-auto">
        <x-kartu class="p-6 sm:p-8">
            <div class="text-center mb-6">
                <div class="inline-flex p-3 bg-green-100 rounded-2xl text-green-700 mb-3">
                    <x-ikon nama="leaf" ukuran="32" kelas="text-green-700" />
                </div>
                <h1 class="text-2xl font-extrabold text-ink">Daftar Akun Siswa</h1>
                <p class="text-sm text-muted mt-1">Buat akun untuk memulai pembelajaran biologi</p>
            </div>

            @if ($errors->any())
                <x-pita-info tipe="galat" class="mb-5">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-pita-info>
            @endif

            <form method="POST" action="{{ route('daftar.kirim') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="nama_lengkap" class="block text-sm font-bold text-ink mb-1">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="username" class="block text-sm font-bold text-ink mb-1">Nama Pengguna (Username)</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-ink mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="kelas" class="block text-sm font-bold text-ink mb-1">Kelas <span class="font-normal text-muted">(opsional)</span></label>
                    <input type="text" id="kelas" name="kelas" value="{{ old('kelas') }}" placeholder="Contoh: XI MIPA 1"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-ink mb-1">Kata Sandi</label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-ink mb-1">Konfirmasi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div class="pt-2">
                    <x-tombol tipe="utama" class="w-full" type="submit">
                        Daftar Sekarang
                    </x-tombol>
                </div>
            </form>

            <div class="mt-6 text-center text-sm text-muted">
                Sudah punya akun?
                <a href="{{ route('masuk') }}" class="font-bold text-green-700 hover:underline">Masuk di sini</a>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
