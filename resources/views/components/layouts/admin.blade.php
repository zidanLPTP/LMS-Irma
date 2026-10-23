<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ?? 'Panel Admin' }} — BioSTEMX</title>

    <!-- Favicon & Icon Web BioSTEMX -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Font Nunito dari Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-ink antialiased bg-bg flex flex-col" x-data="{ bukaMenuHp: false }">
    <!-- Header Mobile & Desktop -->
    <header class="bg-white border-b border-border sticky top-0 z-30">
        <div class="max-w-[1200px] mx-auto px-4 h-16 flex items-center justify-between">
            @php
                $namaSitusApp = \App\Models\Pengaturan::ambil('nama_situs', config('app.name', 'BioSTEMX'));
                $logoSitusApp = \App\Models\Pengaturan::ambil('logo_situs', 'gambar/logo_biostemx.png');
            @endphp
            <div class="flex items-center gap-3">
                <button type="button" @click="bukaMenuHp = !bukaMenuHp" class="lg:hidden p-2 rounded-xl text-ink hover:bg-green-50 focus:outline-none" aria-label="Buka Menu Navigasi">
                    <x-ikon nama="menu" ukuran="24" />
                </button>
                <a href="{{ route('admin.beranda') }}" class="flex items-center gap-2 text-green-700 font-extrabold text-lg">
                    @if ($logoSitusApp && file_exists(public_path($logoSitusApp)))
                        <img src="{{ asset($logoSitusApp) }}" alt="{{ $namaSitusApp }}" width="156" height="36" class="h-9 w-auto max-w-[156px] object-contain">
                    @else
                        <x-ikon nama="leaf" ukuran="24" kelas="text-green-700" />
                        <span>{{ $namaSitusApp }}</span>
                    @endif
                </a>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-muted hidden sm:inline">
                    {{ auth()->user()->nama_lengkap }} (Admin)
                </span>
                <form method="POST" action="{{ route('keluar') }}" class="inline">
                    @csrf
                    <x-tombol tipe="garis" class="!min-h-[38px] !px-3 !py-1.5 text-xs">
                        Keluar
                    </x-tombol>
                </form>
            </div>
        </div>

        <!-- Menu Atas Dropdown HP (Alpine.js) -->
        <div x-show="bukaMenuHp" x-cloak class="lg:hidden border-t border-border bg-white px-4 py-3 space-y-1">
            <a href="{{ route('admin.beranda') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.beranda') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="house" ukuran="18" />
                <span>Beranda</span>
            </a>
            <a href="{{ route('admin.bab.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.bab.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="book-open" ukuran="18" />
                <span>Bab dan Materi</span>
            </a>
            <a href="{{ route('admin.kuis.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.kuis.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="clipboard-list" ukuran="18" />
                <span>Kuis dan Soal</span>
            <a href="{{ route('admin.pengguna.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.pengguna.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="users" ukuran="18" />
                <span>Kelola Pengguna</span>
            </a>
            <a href="{{ route('admin.pengumuman.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.pengumuman.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="megaphone" ukuran="18" />
                <span>Pengumuman</span>
            </a>
            <a href="{{ route('admin.pengaturan.edit') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.pengaturan.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <x-ikon nama="settings" ukuran="18" />
                <span>Pengaturan LMS</span>
            </a>
            @php
                $jumlahAntreanEsai = \App\Models\PercobaanKuis::where('status_pengerjaan', 'selesai')->where('dibatalkan', false)->where('esai_menunggu_nilai', true)->count();
            @endphp
            <a href="{{ route('admin.penilaian-esai.index') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.penilaian-esai.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                <div class="flex items-center gap-2">
                    <x-ikon nama="pencil" ukuran="18" />
                    <span>Penilaian Esai</span>
                </div>
                @if ($jumlahAntreanEsai > 0)
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-black rounded-full bg-amber-500 text-white">
                        {{ $jumlahAntreanEsai }}
                    </span>
                @endif
            </a>
            <div class="pt-2 border-t border-border mt-2">
                <span class="block px-3 py-1 text-xs font-extrabold text-muted uppercase tracking-wider">Pengguna</span>
                <a href="{{ route('admin.pengguna.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.pengguna.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="users" ukuran="18" />
                    <span>Kelola Pengguna</span>
                </a>
            </div>
            <div class="pt-2 border-t border-border mt-2">
                <span class="block px-3 py-1 text-xs font-extrabold text-muted uppercase tracking-wider">Laporan</span>
                <a href="{{ route('admin.laporan.akses') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.laporan.akses*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="clock" ukuran="18" />
                    <span>Laporan Akses</span>
                </a>
                <a href="{{ route('admin.laporan.nilai') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold {{ request()->routeIs('admin.laporan.nilai*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="chart-bar" ukuran="18" />
                    <span>Laporan Nilai</span>
                </a>
            </div>
        </div>
    </header>

    <div class="flex-1 max-w-[1200px] w-full mx-auto px-4 py-6 flex gap-6">
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:block w-64 shrink-0 space-y-4">
            <div class="bg-white rounded-2xl border border-border p-3 space-y-1 shadow-[0_1px_3px_rgba(31,45,42,0.08)]">
                <span class="block px-3 py-1.5 text-xs font-extrabold text-muted uppercase tracking-wider">Menu Konten</span>
                <a href="{{ route('admin.beranda') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.beranda') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="house" ukuran="20" />
                    <span>Beranda</span>
                </a>
                <a href="{{ route('admin.bab.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.bab.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="book-open" ukuran="20" />
                    <span>Bab dan Materi</span>
                </a>
                <a href="{{ route('admin.kuis.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.kuis.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="clipboard-list" ukuran="20" />
                    <span>Kuis dan Soal</span>
                </a>
                <a href="{{ route('admin.penilaian-esai.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.penilaian-esai.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <div class="flex items-center gap-2">
                        <x-ikon nama="pencil" ukuran="20" />
                        <span>Penilaian Esai</span>
                    </div>
                    @if ($jumlahAntreanEsai > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-black rounded-full bg-amber-500 text-white">
                            {{ $jumlahAntreanEsai }}
                        </span>
                    @endif
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-border p-3 space-y-1 shadow-[0_1px_3px_rgba(31,45,42,0.08)]">
                <span class="block px-3 py-1.5 text-xs font-extrabold text-muted uppercase tracking-wider">Pengguna & Komunikasi</span>
                <a href="{{ route('admin.pengguna.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.pengguna.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="users" ukuran="20" />
                    <span>Kelola Pengguna</span>
                </a>
                <a href="{{ route('admin.pengumuman.index') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.pengumuman.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="megaphone" ukuran="20" />
                    <span>Pengumuman</span>
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-border p-3 space-y-1 shadow-[0_1px_3px_rgba(31,45,42,0.08)]">
                <span class="block px-3 py-1.5 text-xs font-extrabold text-muted uppercase tracking-wider">Laporan Penelitian</span>
                <a href="{{ route('admin.laporan.akses') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.laporan.akses*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="clock" ukuran="20" />
                    <span>Laporan Akses</span>
                </a>
                <a href="{{ route('admin.laporan.nilai') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.laporan.nilai*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="chart-bar" ukuran="20" />
                    <span>Laporan Nilai</span>
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-border p-3 space-y-1 shadow-[0_1px_3px_rgba(31,45,42,0.08)]">
                <span class="block px-3 py-1.5 text-xs font-extrabold text-muted uppercase tracking-wider">Sistem</span>
                <a href="{{ route('admin.pengaturan.edit') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition-colors {{ request()->routeIs('admin.pengaturan.*') ? 'bg-green-100 text-green-700' : 'text-ink hover:bg-green-50' }}">
                    <x-ikon nama="settings" ukuran="20" />
                    <span>Pengaturan LMS</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0">
            @if (session('sukses'))
                <x-pita-info tipe="sukses" class="mb-5">
                    {{ session('sukses') }}
                </x-pita-info>
            @endif

            @if (session('galat'))
                <x-pita-info tipe="galat" class="mb-5">
                    {{ session('galat') }}
                </x-pita-info>
            @endif

            {{ $slot }}
        </main>
    </div>

    <footer class="bg-white border-t border-border py-4 mt-auto">
        <div class="max-w-[1200px] mx-auto px-4 text-center text-xs text-muted">
            &copy; {{ date('Y') }} Panel Admin LMS Penelitian Sekolah.
        </div>
    </footer>
</body>
</html>
