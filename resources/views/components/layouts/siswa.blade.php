<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ?? 'BioSTEMX' }} — Pembelajaran Biologi</title>

    <!-- Favicon & Icon Web BioSTEMX -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Font Nunito dari Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/detak-jantung.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans text-ink antialiased bg-bg flex flex-col"
    data-url-detak="{{ route('siswa.detak') }}"
    data-interval-detak="{{ config('lms.interval_detak_detik', 45) }}"
    data-batas-diam="{{ config('lms.batas_diam_detik', 1800) }}">
    <!-- Bilah Atas Siswa Sesuai Spesifikasi -->
    <header class="bg-white border-b border-border sticky top-0 z-30">
        <div class="max-w-[1000px] mx-auto px-4 h-16 flex items-center justify-between">
            @php
                $namaSitusApp = \App\Models\Pengaturan::ambil('nama_situs', config('app.name', 'Biologi SMA'));
                $logoSitusApp = \App\Models\Pengaturan::ambil('logo_situs');
            @endphp
            <div class="flex items-center gap-6">
                <a href="{{ route('siswa.beranda') }}" class="flex items-center gap-2 text-green-700 font-extrabold text-lg">
                    @if ($logoSitusApp && file_exists(public_path($logoSitusApp)))
                        <img src="{{ asset($logoSitusApp) }}" alt="Logo" class="h-8 w-auto max-w-[120px] object-contain">
                    @else
                        <x-ikon nama="leaf" ukuran="24" kelas="text-green-700" />
                    @endif
                    <span>{{ $namaSitusApp }}</span>
                </a>

                <!-- Navigasi Laptop / Tablet (Top Bar) -->
                <nav class="hidden sm:flex items-center gap-1">
                    <a href="{{ route('siswa.beranda') }}"
                        class="px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-colors {{ request()->routeIs('siswa.beranda') ? 'bg-green-100 text-green-800' : 'text-muted hover:text-ink hover:bg-surface' }}">
                        <x-ikon nama="house" ukuran="18" />
                        <span>Beranda</span>
                    </a>
                    <a href="{{ route('siswa.nilai.index') }}"
                        class="px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-colors {{ request()->routeIs('siswa.nilai.*') ? 'bg-green-100 text-green-800' : 'text-muted hover:text-ink hover:bg-surface' }}">
                        <x-ikon nama="award" ukuran="18" />
                        <span>Nilai Saya</span>
                    </a>
                    <a href="{{ route('siswa.pengumuman.index') }}"
                        class="px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-colors {{ request()->routeIs('siswa.pengumuman.*') ? 'bg-green-100 text-green-800' : 'text-muted hover:text-ink hover:bg-surface' }}">
                        <x-ikon nama="megaphone" ukuran="18" />
                        <span>Pengumuman</span>
                    </a>
                    <a href="{{ route('siswa.profil.edit') }}"
                        class="px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-colors {{ request()->routeIs('siswa.profil.*') ? 'bg-green-100 text-green-800' : 'text-muted hover:text-ink hover:bg-surface' }}">
                        <x-ikon nama="user" ukuran="18" />
                        <span>Profil</span>
                    </a>
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Kolom Pencarian Siswa di Header -->
                <form method="GET" action="{{ route('siswa.cari') }}" class="relative hidden md:block">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari bab / materi..." minlength="2" required
                        class="w-44 lg:w-56 rounded-xl border border-border pl-8 pr-3 py-1.5 text-xs text-ink bg-surface focus:bg-white focus:outline-none focus:ring-2 focus:ring-green-700 transition-all">
                    <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none">
                        <x-ikon nama="search" ukuran="14" />
                    </div>
                </form>

                <a href="{{ route('siswa.cari') }}" class="md:hidden p-2 rounded-xl text-ink hover:bg-surface transition-colors" aria-label="Cari Materi">
                    <x-ikon nama="search" ukuran="20" kelas="text-ink" />
                </a>

                @php
                    $jumlahNotifikasiBelumDibaca = auth()->user()->notifikasi()->whereNull('dibaca_pada')->count();
                @endphp
                <!-- Ikon Lonceng Notifikasi Lucide -->
                <a href="{{ route('siswa.notifikasi.index') }}" class="relative p-2 rounded-xl text-ink hover:bg-surface transition-colors" aria-label="Notifikasi">
                    <x-ikon nama="bell" ukuran="20" kelas="text-ink" />
                    @if ($jumlahNotifikasiBelumDibaca > 0)
                        <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-black leading-none text-white bg-rose-600 rounded-full">
                            {{ $jumlahNotifikasiBelumDibaca > 99 ? '99+' : $jumlahNotifikasiBelumDibaca }}
                        </span>
                    @endif
                </a>

                <div class="text-right hidden sm:block">
                    <span class="block text-sm font-extrabold text-ink leading-tight">
                        {{ auth()->user()->nama_lengkap }}
                    </span>
                    <span class="block text-xs text-muted font-bold">
                        {{ auth()->user()->kelas ?? 'Siswa' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('keluar') }}" class="inline" onsubmit="try { Object.keys(localStorage).forEach(k => { if (k.startsWith('draf_esai_')) localStorage.removeItem(k); }); } catch (e) {}">
                    @csrf
                    <x-tombol tipe="garis" class="!min-h-[38px] !px-3.5 !py-1.5 text-xs font-bold">
                        Keluar
                    </x-tombol>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-[1000px] w-full mx-auto px-4 py-6 pb-24 sm:pb-6">
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

    <!-- Bilah Bawah Siswa di Layar HP (Bottom Bar with safe-area-inset-bottom) -->
    <nav class="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-border z-30 px-2 py-2 flex items-center justify-around"
         style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
        <a href="{{ route('siswa.beranda') }}"
            class="flex flex-col items-center gap-1 text-[11px] font-bold py-1 px-2 rounded-xl {{ request()->routeIs('siswa.beranda') ? 'text-green-700' : 'text-muted hover:text-ink' }}">
            <x-ikon nama="house" ukuran="20" />
            <span>Beranda</span>
        </a>
        <a href="{{ route('siswa.nilai.index') }}"
            class="flex flex-col items-center gap-1 text-[11px] font-bold py-1 px-2 rounded-xl {{ request()->routeIs('siswa.nilai.*') ? 'text-green-700' : 'text-muted hover:text-ink' }}">
            <x-ikon nama="award" ukuran="20" />
            <span>Nilai</span>
        </a>
        <a href="{{ route('siswa.pengumuman.index') }}"
            class="flex flex-col items-center gap-1 text-[11px] font-bold py-1 px-2 rounded-xl {{ request()->routeIs('siswa.pengumuman.*') ? 'text-green-700' : 'text-muted hover:text-ink' }}">
            <x-ikon nama="megaphone" ukuran="20" />
            <span>Pengumuman</span>
        </a>
        <a href="{{ route('siswa.profil.edit') }}"
            class="flex flex-col items-center gap-1 text-[11px] font-bold py-1 px-2 rounded-xl {{ request()->routeIs('siswa.profil.*') ? 'text-green-700' : 'text-muted hover:text-ink' }}">
            <x-ikon nama="user" ukuran="20" />
            <span>Profil</span>
        </a>
    </nav>

    <footer class="bg-white border-t border-border py-4 mt-auto hidden sm:block">
        <div class="max-w-[1000px] mx-auto px-4 text-center text-xs text-muted">
            &copy; {{ date('Y') }} {{ $namaSitusApp }}. Semua Hak Dilindungi.
        </div>
    </footer>
</body>
</html>
