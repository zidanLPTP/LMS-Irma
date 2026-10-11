@props([
    'nama',
    'ukuran' => '24',
    'kelas' => '',
])

@php
    $path = resource_path("svg/lucide/{$nama}.svg");
@endphp

@if (file_exists($path))
    {!! str_replace(
        '<svg ',
        '<svg class="' . e($kelas) . '" width="' . e($ukuran) . '" height="' . e($ukuran) . '" ',
        file_get_contents($path)
    ) !!}
@endif
