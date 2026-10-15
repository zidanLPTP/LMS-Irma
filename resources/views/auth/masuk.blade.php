<x-layouts.induk judul="Masuk ke LMS">
    <div class="max-w-md mx-auto">
        <x-kartu class="p-6 sm:p-8">
            @php
                $namaSitusApp = \App\Models\Pengaturan::ambil('nama_situs', config('app.name', 'LMS Penelitian'));
                $logoSitusApp = \App\Models\Pengaturan::ambil('logo_situs');
            @endphp
            <div class="text-center mb-6">
                <div class="inline-flex p-3 bg-green-100 rounded-2xl text-green-700 mb-3">
                    @if ($logoSitusApp && file_exists(public_path($logoSitusApp)))
                        <img src="{{ asset($logoSitusApp) }}" alt="Logo" class="h-10 w-auto max-w-[120px] object-contain">
                    @else
                        <x-ikon nama="leaf" ukuran="32" kelas="text-green-700" />
                    @endif
                </div>
                <h1 class="text-2xl font-extrabold text-ink">{{ $namaSitusApp }}</h1>
                <p class="text-sm text-muted mt-1">Gunakan nama pengguna atau alamat surel terdaftar</p>
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

            <form method="POST" action="{{ route('masuk.kirim') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="login" class="block text-sm font-bold text-ink mb-1">Nama Pengguna atau Email</label>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-ink mb-1">Kata Sandi</label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-muted">
                        <input type="checkbox" name="ingat_saya" value="1"
                            class="rounded border-border text-green-700 focus:ring-green-700">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <x-tombol tipe="utama" class="w-full" type="submit">
                        Masuk
                    </x-tombol>
                </div>
            </form>

            <div class="mt-6 text-center text-sm text-muted">
                Belum punya akun?
                <a href="{{ route('daftar') }}" class="font-bold text-green-700 hover:underline">Daftar sekarang</a>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
