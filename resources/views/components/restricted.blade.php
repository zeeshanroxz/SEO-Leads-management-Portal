@props(['title' => 'Restricted by Admin', 'text' => 'An Admin has restricted this field group for your role on this record.'])
<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4']) }}>
    <x-ico name="lock-closed" class="mt-0.5 size-5 text-slate-400" />
    <div>
        <p class="text-sm font-medium text-slate-800">{{ $title }}</p>
        <p class="mt-0.5 text-sm text-slate-500">{{ $text }}</p>
    </div>
</div>
