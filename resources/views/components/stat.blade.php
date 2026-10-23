@props(['label', 'value', 'hint' => null, 'tone' => 'slate', 'icon' => null, 'href' => null])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-600', 'brand' => 'bg-brand-50 text-brand-600', 'blue' => 'bg-blue-50 text-blue-600',
        'green' => 'bg-emerald-50 text-emerald-600', 'amber' => 'bg-amber-50 text-amber-600', 'red' => 'bg-red-50 text-red-600',
        'violet' => 'bg-violet-50 text-violet-600', 'teal' => 'bg-teal-50 text-teal-600', 'orange' => 'bg-orange-50 text-orange-600', 'indigo' => 'bg-indigo-50 text-indigo-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card flex items-start gap-3 p-4 '.($href ? 'transition hover:border-brand-300 hover:shadow-sm' : '')]) }}>
    @if ($icon)
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['slate'] }}"><x-ico :name="$icon" class="size-5" /></span>
    @endif
    <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
        @if ($hint)<p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>@endif
    </div>
</{{ $tag }}>
