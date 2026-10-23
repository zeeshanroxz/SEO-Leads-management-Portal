@props(['name', 'title', 'size' => 'md', 'description' => null])
@php($w = ['sm' => 'max-w-md', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'][$size] ?? 'max-w-lg')
<div x-cloak x-show="$store.modal.is('{{ $name }}')" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title"
     @keydown.escape.window="$store.modal.is('{{ $name }}') && $store.modal.close()">
    <div class="absolute inset-0 bg-slate-900/50" x-show="$store.modal.is('{{ $name }}')" x-transition.opacity @click="$store.modal.close()"></div>
    <div class="relative flex max-h-[92vh] w-full {{ $w }} flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-2xl"
         x-show="$store.modal.is('{{ $name }}')" x-transition.scale.95.opacity.duration.150ms x-trap.noscroll.inert="$store.modal.is('{{ $name }}')">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 id="modal-{{ $name }}-title" class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @if ($description)<p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>@endif
            </div>
            <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-1" @click="$store.modal.close()" aria-label="Close dialog"><x-ico name="x-mark" class="size-5" /></button>
        </div>
        <div class="overflow-y-auto px-5 py-4">{{ $slot }}</div>
    </div>
</div>
