{{-- Pagination + count for an element using x-data="dataTable()" --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
    <p>Showing <span class="font-medium text-slate-800" x-text="from"></span>–<span class="font-medium text-slate-800" x-text="to"></span> of <span class="font-medium text-slate-800" x-text="matched"></span></p>
    <div class="flex items-center gap-1" x-show="pages > 1">
        <button type="button" class="btn btn-secondary btn-xs" :disabled="page <= 1" @click="go(page - 1)" aria-label="Previous page"><x-ico name="chevron-left" class="size-4" /></button>
        <span class="px-2">Page <span x-text="page"></span> / <span x-text="pages"></span></span>
        <button type="button" class="btn btn-secondary btn-xs" :disabled="page >= pages" @click="go(page + 1)" aria-label="Next page"><x-ico name="chevron-right" class="size-4" /></button>
    </div>
</div>
<div x-show="matched === 0" x-cloak>
    <x-empty-state icon="magnifying-glass" title="No results match your filters" text="Try clearing the search or changing a filter.">
        <button type="button" class="btn btn-secondary btn-sm" @click="reset()">Clear filters</button>
    </x-empty-state>
</div>
