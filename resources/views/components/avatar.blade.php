@props(['name' => '', 'size' => 'sm'])
@php
    $palette = ['bg-brand-100 text-brand-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-800', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700', 'bg-teal-100 text-teal-700'];
    $tone = $palette[crc32($name) % count($palette)];
    $dim = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-lg'][$size] ?? 'size-8 text-xs';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex $dim shrink-0 items-center justify-center rounded-full font-semibold $tone"]) }} title="{{ $name }}" aria-hidden="true">{{ initials($name) }}</span>
