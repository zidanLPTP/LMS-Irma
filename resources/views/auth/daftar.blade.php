<x-layouts.induk judul="Daftar Akun Siswa">
    <div class="max-w-md mx-auto">
        <x-kartu class="p-6 sm:p-8">
            @php
                $namaSitusApp = \App\Models\Pengaturan::ambil('nama_situs', config('app.name', 'BioSTEMX'));
                $logoSitusApp = \App\Models\Pengaturan::ambil('logo_situs', 'gambar/logo_biostemx.png');
            @endphp
            <div class="text-center mb-6">
                @if ($logoSitusApp && file_exists(public_path($logoSitusApp)))
                    <div class="flex justify-center mb-3">
                        <img src="{{ asset($logoSitusApp) }}" alt="{{ $namaSitusApp }}" class="h-14 sm:h-16 w-auto max-w-[220px] object-contain">
                    </div>
                @else
                    <div class="inline-flex p-3 bg-green-50 rounded-2xl text-green-700 mb-2">
                        <x-ikon nama="leaf" ukuran="36" kelas="text-green-700" />
                    </div>
                @endif
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

            @if (! ($pendaftaranBuka ?? true))
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2 mb-4">
                    <x-ikon nama="lock" ukuran="28" kelas="text-amber-700 mx-auto" />
                    <h2 class="text-sm font-extrabold text-amber-900">Pendaftaran Siswa Ditutup</h2>
                    <p class="text-xs text-amber-800">
                        Pendaftaran akun siswa baru saat ini sedang dinonaktifkan oleh administrator sekolah. Silakan hubungi guru pembimbing Anda jika Anda belum memiliki akun.
                    </p>
                </div>
            @else
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

                    @if ($butuhKode ?? false)
                        <div>
                            <label for="kode_pendaftaran" class="block text-sm font-bold text-ink mb-1">Kode Pendaftaran Khusus</label>
                            <input type="text" id="kode_pendaftaran" name="kode_pendaftaran" value="{{ old('kode_pendaftaran') }}" required
                                placeholder="Masukkan kode dari guru pembimbing"
                                class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        </div>
                    @endif

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
            @endif

            <div class="mt-6 text-center text-sm text-muted">
                Sudah punya akun?
                <a href="{{ route('masuk') }}" class="font-bold text-green-700 hover:underline">Masuk di sini</a>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
