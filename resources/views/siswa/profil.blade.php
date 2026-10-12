<x-layouts.siswa :judul="__('profil.judul_halaman')">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('profil.judul_halaman') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('profil.deskripsi_halaman') }}</p>
        </div>

        @if (session('status'))
            <x-pita-info tipe="sukses">
                {{ session('status') }}
            </x-pita-info>
        @endif

        @if ($errors->any())
            <x-pita-info tipe="galat">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-pita-info>
        @endif

        <form method="POST" action="{{ route('siswa.profil.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-kartu class="p-6 sm:p-8 space-y-5">
                <!-- Username (Terkunci) -->
                <div>
                    <label class="block text-sm font-bold text-ink mb-1">
                        {{ __('profil.username') }}
                    </label>
                    <input type="text" value="{{ $user->username }}" disabled
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-muted bg-surface cursor-not-allowed text-sm font-mono">
                    <p class="text-xs text-muted mt-1">{{ __('profil.username_keterangan') }}</p>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="nama_lengkap" class="block text-sm font-bold text-ink mb-1">
                        {{ __('profil.nama_lengkap') }} <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap"
                        value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <!-- Kelas -->
                <div>
                    <label for="kelas" class="block text-sm font-bold text-ink mb-1">
                        {{ __('profil.kelas') }}
                    </label>
                    <input type="text" id="kelas" name="kelas"
                        value="{{ old('kelas', $user->kelas) }}" placeholder="Contoh: XII MIPA 1"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-bold text-ink mb-1">
                        {{ __('profil.email') }} <span class="text-rose-600">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                        value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <!-- Password Saat Ini (Hanya Wajib Jika Email Berubah) -->
                <div class="pt-2 border-t border-border">
                    <label for="password_saat_ini" class="block text-sm font-bold text-ink mb-1">
                        {{ __('profil.password_konfirmasi_email') }}
                    </label>
                    <input type="password" id="password_saat_ini" name="password_saat_ini"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                    <p class="text-xs text-muted mt-1">Hanya perlu diisi jika Anda mengubah alamat email di atas.</p>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="{{ route('siswa.password.edit') }}" class="text-sm font-bold text-green-700 hover:underline flex items-center gap-1.5">
                        <x-ikon nama="key-round" ukuran="16" />
                        <span>{{ __('profil.tautan_ganti_password') }}</span>
                    </a>

                    <x-tombol tipe="utama" type="submit" class="w-full sm:w-auto !px-6">
                        Simpan Profil
                    </x-tombol>
                </div>
            </x-kartu>
        </form>
    </div>
</x-layouts.siswa>
