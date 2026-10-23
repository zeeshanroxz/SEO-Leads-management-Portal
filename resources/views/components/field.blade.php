@props(['label', 'name' => null, 'help' => null, 'required' => false, 'error' => null])
<div {{ $attributes->class(['min-w-0']) }}>
    <label @if ($name) for="{{ $name }}" @endif class="label">{{ $label }}@if ($required)<span class="text-red-500" aria-hidden="true"> *</span>@endif</label>
    {{ $slot }}
    @if ($help)<p class="help">{{ $help }}</p>@endif
    @if ($error)<p class="err">{{ $error }}</p>@endif
</div>
