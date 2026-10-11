@props([
    'as' => 'div',
])

<{{ $as }} {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-border p-5 shadow-[0_1px_3px_rgba(31,45,42,0.08)]']) }}>
    {{ $slot }}
</{{ $as }}>
