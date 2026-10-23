@props(['full' => true])
<div class="flex flex-wrap items-center gap-0.5 rounded-t-lg border border-b-0 border-slate-300 bg-slate-50 px-2 py-1.5" role="toolbar" aria-label="Formatting">
    @php
        $btn = 'btn btn-ghost btn-icon size-8 text-slate-600';
    @endphp
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="exec('bold')" aria-label="Bold" title="Bold"><x-ico name="bold" class="size-4" /></button>
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="exec('italic')" aria-label="Italic" title="Italic"><x-ico name="italic" class="size-4" /></button>
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="exec('underline')" aria-label="Underline" title="Underline"><x-ico name="underline" class="size-4" /></button>
    <span class="mx-1 h-5 w-px bg-slate-300"></span>
    @if ($full)
        <button type="button" class="{{ $btn }} w-9 text-xs font-bold" @mousedown.prevent @click="block('h2')" title="Heading 2" aria-label="Heading 2">H2</button>
        <button type="button" class="{{ $btn }} w-9 text-xs font-bold" @mousedown.prevent @click="block('h3')" title="Heading 3" aria-label="Heading 3">H3</button>
        <button type="button" class="{{ $btn }} w-9 text-xs" @mousedown.prevent @click="block('p')" title="Paragraph" aria-label="Paragraph">¶</button>
        <span class="mx-1 h-5 w-px bg-slate-300"></span>
    @endif
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="exec('insertUnorderedList')" aria-label="Bulleted list" title="Bulleted list"><x-ico name="list-bullet" class="size-4" /></button>
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="exec('insertOrderedList')" aria-label="Numbered list" title="Numbered list"><x-ico name="numbered-list" class="size-4" /></button>
    <button type="button" class="{{ $btn }}" @mousedown.prevent @click="openLink()" aria-label="Insert link" title="Insert link"><x-ico name="link" class="size-4" /></button>
    @if ($full)
        <span class="mx-1 h-5 w-px bg-slate-300"></span>
        <button type="button" class="{{ $btn }}" @mousedown.prevent @click="table()" aria-label="Insert table" title="Insert table"><x-ico name="table-cells" class="size-4" /></button>
        <button type="button" class="{{ $btn }}" @mousedown.prevent @click="chart()" aria-label="Insert sample chart" title="Insert sample chart (demo)"><x-ico name="chart-bar" class="size-4" /></button>
        <button type="button" class="{{ $btn }}" @mousedown.prevent @click="image()" aria-label="Insert sample image" title="Insert sample image (demo)"><x-ico name="photo" class="size-4" /></button>
        <label class="{{ $btn }} cursor-pointer" title="Upload image (demo, max 300 KB)"><x-ico name="arrow-up-tray" class="size-4" /><span class="sr-only">Upload image</span><input type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="sr-only" @change="upload($event)"></label>
        <span class="mx-1 h-5 w-px bg-slate-300"></span>
        <button type="button" class="btn btn-ghost btn-sm text-amber-700 hover:bg-amber-50" @mousedown.prevent @click="internalBlock()" title="Insert an internal-only block (excluded from the customer version)"><x-ico name="eye-slash" class="size-4" /> Internal block</button>
    @endif
    <div x-cloak x-show="linkOpen" class="ml-auto flex items-center gap-1.5" @keydown.enter.prevent="applyLink()" @keydown.escape="linkOpen = false">
        <input x-ref="link" x-model="linkUrl" class="input h-8 w-56 text-xs" aria-label="Link URL" placeholder="https://…"><button type="button" class="btn btn-primary btn-xs" @click="applyLink()">Apply</button><button type="button" class="btn btn-ghost btn-xs" @click="linkOpen = false">Cancel</button>
    </div>
</div>
