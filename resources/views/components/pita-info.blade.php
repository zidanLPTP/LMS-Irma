@props([
    'tipe' => 'info', // sukses, info, peringatan, galat
])

@php
    $gaya = match ($tipe) {
        'sukses' => 'bg-green-100 text-green-800 border-green-300 font-medium',
        'info' => 'bg-sky-100 text-sky-700 border-sky-300 font-medium',
        'peringatan' => 'bg-[#FEF3C7] text-warning-700 border-[#FDE68A] font-medium',
        'galat' => 'bg-[#FDE8E7] text-danger border-[#F8C1BE] font-medium',
        default => 'bg-sky-100 text-sky-700 border-sky-300 font-medium',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border text-sm {$gaya}"]) }}>
    <div class="flex-1">
        {{ $slot }}
    </div>
</div>
