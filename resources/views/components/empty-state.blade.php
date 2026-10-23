@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-3 flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-ico :name="$icon" class="size-6" /></span>
    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    <div class="mt-4">{{ $slot }}</div>
</div>
