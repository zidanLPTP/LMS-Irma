@props([
    'status', // lulus, belum_lulus_lanjut, belum_lulus, menunggu_penilaian_esai
])

@php
    $gaya = match ($status) {
        'lulus' => 'bg-green-100 text-success border border-green-300',
        'belum_lulus_lanjut' => 'bg-sky-100 text-sky-700 border border-sky-300',
        'belum_lulus' => 'bg-[#FDE8E7] text-danger border border-[#F8C1BE]',
        'menunggu_penilaian_esai' => 'bg-[#FEF3C7] text-warning border border-[#FDE68A]',
        default => 'bg-gray-100 text-muted border border-border',
    };

    $label = match ($status) {
        'lulus' => 'Lulus',
        'belum_lulus_lanjut' => 'Belum lulus, lanjut',
        'belum_lulus' => 'Belum lulus',
        'menunggu_penilaian_esai' => 'Menunggu penilaian esai',
        default => '-',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {$gaya}"]) }}>
    {{ $label }}
</span>
