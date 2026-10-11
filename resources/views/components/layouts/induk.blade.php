<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ?? 'LMS Penelitian Sekolah' }}</title>

    <!-- Font Nunito dari Google Fonts (cadangan sistem sans-serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-ink antialiased bg-bg flex flex-col justify-between">
    <header class="bg-white border-b border-border sticky top-0 z-30">
        <div class="max-w-[1100px] mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-green-700 font-extrabold text-lg">
                <x-ikon nama="leaf" ukuran="24" kelas="text-green-700" />
                <span>LMS Biologi</span>
            </a>

            @auth
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-muted hidden sm:inline">
                        {{ auth()->user()->nama_lengkap }} ({{ auth()->user()->peran }})
                    </span>
                    <form method="POST" action="{{ route('keluar') }}" class="inline">
                        @csrf
                        <x-tombol tipe="garis" class="!min-h-[38px] !px-3 !py-1.5 text-xs">
                            Keluar
                        </x-tombol>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="flex-1 max-w-[1100px] w-full mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-border py-4 mt-auto">
        <div class="max-w-[1100px] mx-auto px-4 text-center text-xs text-muted">
            &copy; {{ date('Y') }} LMS Penelitian Biologi. Seluruh hak cipta dilindungi.
        </div>
    </footer>
</body>
</html>
