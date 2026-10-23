@props(['name', 'solid' => false])
<x-dynamic-component :component="($solid ? 'heroicon-s-' : 'heroicon-o-').$name" {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} aria-hidden="true" />
