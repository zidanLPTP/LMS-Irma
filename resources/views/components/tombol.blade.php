@props([
    'tipe' => 'utama', // utama, sekunder, garis, bahaya
    'href' => null,
])

@php
    $kelasDasar = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 font-bold text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:bg-locked disabled:cursor-not-allowed cursor-pointer min-h-[44px]';

    $varian = match ($tipe) {
        'utama' => 'bg-green-700 text-white hover:bg-[#25664a] focus:ring-green-700',
        'sekunder' => 'bg-sky-700 text-white hover:bg-[#18587d] focus:ring-sky-700',
        'garis' => 'border-2 border-green-700 text-green-700 bg-transparent hover:bg-green-50 focus:ring-green-700',
        'bahaya' => 'bg-danger text-white hover:bg-[#a63934] focus:ring-danger',
        default => 'bg-green-700 text-white hover:bg-[#25664a]',
    };

    $semuaKelas = "{$kelasDasar} {$varian}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $semuaKelas]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $semuaKelas]) }}>
        {{ $slot }}
    </button>
@endif
