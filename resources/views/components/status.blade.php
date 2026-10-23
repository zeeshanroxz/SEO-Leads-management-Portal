@props(['dim', 'value'])
@php([$label, $color] = status_meta($dim, $value))
<span {{ $attributes->merge(['class' => 'chip chip-'.$color]) }}>{{ $label }}</span>
