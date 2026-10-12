<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ?? 'LMS Siswa' }} — Pembelajaran Biologi</title>

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
            <a href="{{ route('siswa.beranda') }}" class="flex items-center gap-2 text-green-700 font-extrabold text-lg">
                <x-ikon nama="leaf" ukuran="24" kelas="text-green-700" />
                <span>Biologi SMA</span>
            </a>

            <div class="flex items-center gap-4">
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

    <main class="flex-1 max-w-[1000px] w-full mx-auto px-4 py-6">
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

    <footer class="bg-white border-t border-border py-4 mt-auto">
        <div class="max-w-[1000px] mx-auto px-4 text-center text-xs text-muted">
            &copy; {{ date('Y') }} LMS Penelitian Biologi Sekolah. Semua Hak Dilindungi.
        </div>
    </footer>
</body>
</html>
