@props([
    'tipe' => 'info', // sukses, info, peringatan, galat
])

@php
    $gaya = match ($tipe) {
        'sukses' => 'bg-green-100 text-success border-green-300',
        'info' => 'bg-sky-100 text-sky-700 border-sky-300',
        'peringatan' => 'bg-[#FEF3C7] text-warning border-[#FDE68A]',
        'galat' => 'bg-[#FDE8E7] text-danger border-[#F8C1BE]',
        default => 'bg-sky-100 text-sky-700 border-sky-300',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border text-sm {$gaya}"]) }}>
    <div class="flex-1">
        {{ $slot }}
    </div>
</div>
