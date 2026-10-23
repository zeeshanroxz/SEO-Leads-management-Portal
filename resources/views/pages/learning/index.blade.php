<x-app-layout title="Learning library" :crumbs="[['Learning library']]" subtitle="Scripts, playbooks and policy updates published by Admin, with audience targeting and version history.">
    <x-slot:actions>@perm('admin.settings')<button type="button" class="btn btn-primary" @click="$store.modal.open('publish')"><x-ico name="arrow-up-tray" class="size-4" /> Publish document</button>@endperm</x-slot:actions>

    <div class="card" x-data="dataTable({ perPage: 8, sortKey: 'date', sortDir: 'desc', filters: { cat: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1"><label for="l-search" class="sr-only">Search documents</label><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input id="l-search" type="search" x-model="q" class="input pl-9" placeholder="Search title, description, category…"></div>
            <select x-model="filters.cat" class="select w-48" aria-label="Category"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select>
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Document</th><th>Category</th><th>Audience</th><th>Version</th><th class="th-sort" @click="sort('date')">Published <span x-text="arrow('date')"></span></th>@perm('admin.settings')<th class="text-right">Opens</th>@endperm<th class="w-28"></th></tr></thead>
            <tbody>
                @foreach ($docs as $d)
                    <tr data-row data-search="{{ strtolower($d->title.' '.$d->description.' '.$d->category) }}" data-f-cat="{{ $d->category }}" data-s-date="{{ $d->published_at }}">
                        <td><div class="flex items-start gap-3"><span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500">{{ $d->file_type }}</span><div class="min-w-0"><p class="font-medium text-slate-900">{{ $d->title }}</p><p class="line-clamp-1 text-xs text-slate-500">{{ $d->description }}</p></div></div></td>
                        <td>{{ $d->category }}</td>
                        <td>@foreach ((array) $d->audience->labels as $l)<span class="chip chip-slate mr-1">{{ $l }}</span>@endforeach</td>
                        <td>v{{ $d->version }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ fdate($d->published_at) }}</td>
                        @perm('admin.settings')<td class="text-right">{{ $d->read_count }}</td>@endperm
                        <td class="text-right"><button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('doc', @js($d->plain()))">Open</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
    </div>

    <x-slot:modals>
        <x-modal name="doc" title="Document" size="lg">
            <div class="space-y-4" x-data>
                <div><h3 class="text-lg font-semibold" x-text="$store.modal.payload.title"></h3><p class="text-sm text-slate-500" x-text="$store.modal.payload.category + ' · ' + $store.modal.payload.file_type + ' · ' + $store.modal.payload.size"></p></div>
                <p class="text-sm text-slate-700" x-text="$store.modal.payload.description"></p>
                <div class="rounded-lg border border-slate-200 p-3"><p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Version history</p>
                    <ul class="space-y-1 text-sm"><template x-for="v in ($store.modal.payload.versions || [])" :key="v.no"><li class="flex items-center justify-between"><span x-text="'Version ' + v.no + ' — ' + v.note"></span><span class="text-xs text-slate-400" x-text="v.at.slice(0, 10)"></span></li></template></ul></div>
                <div class="flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.toast.push('Download is simulated — no file exists in Phase 1.', 'info', { demo: true })"><x-ico name="arrow-down-tray" class="size-4" /> Download</button><button type="button" class="btn btn-primary" @click="$store.toast.push('Marked as read.'); $store.modal.close()"><x-ico name="check" class="size-4" /> Mark as read</button></div>
            </div>
        </x-modal>

        @perm('admin.settings')
        <x-modal name="publish" title="Publish learning document" description="Choose who sees it and how they are told. Nothing is uploaded or emailed in Phase 1." size="lg">
            <form method="POST" action="{{ route('learning.store') }}" x-data="{ title: '', desc: '', type: 'all', file: '', progress: 0, touched: false, uploading: false,
                pick(e) { const f = e.target.files[0]; if (!f) return; this.file = f.name; this.progress = 0; this.uploading = true; const t = setInterval(() => { this.progress = Math.min(100, this.progress + 20); if (this.progress >= 100) { clearInterval(t); this.uploading = false; } }, 120); } }"
                  @submit="touched = true; if (!title.trim() || desc.trim().length < 10) $event.preventDefault()">
                @csrf
                <input type="hidden" name="file_name" :value="file"><input type="hidden" name="file_type" value="PDF">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Title" name="title" :required="true" class="sm:col-span-2"><input id="title" name="title" x-model="title" class="input" :class="{ 'input-error': touched && !title.trim() }"><p class="err" x-show="touched && !title.trim()" x-cloak>Title is required.</p></x-field>
                    <x-field label="Category" name="category"><select id="category" name="category" class="select">@foreach ($categories as $c)<option>{{ $c }}</option>@endforeach</select></x-field>
                    <x-field label="File (demo)" name="file"><input id="file" type="file" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" @change="pick($event)"><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100" x-show="file" x-cloak><div class="h-full bg-brand-500 transition-all" :style="'width:' + progress + '%'"></div></div><p class="help" x-show="file" x-cloak x-text="uploading ? 'Uploading (simulated)…' : file + ' attached (nothing stored)'"></p></x-field>
                    <x-field label="Description" name="description" :required="true" class="sm:col-span-2"><textarea id="description" name="description" x-model="desc" rows="3" class="textarea" :class="{ 'input-error': touched && desc.trim().length < 10 }"></textarea><p class="err" x-show="touched && desc.trim().length < 10" x-cloak>Add a description (min. 10 characters).</p></x-field>
                    <x-field label="Audience" name="audience_type" class="sm:col-span-2"><select id="audience_type" name="audience_type" x-model="type" class="select"><option value="all">Everyone</option><option value="roles">Selected roles</option><option value="teams">Selected teams</option><option value="projects">Selected projects</option></select></x-field>
                    <div class="sm:col-span-2" x-show="type !== 'all'" x-cloak><p class="label">Choose targets</p>
                        <div class="flex flex-wrap gap-2" x-show="type === 'roles'">@foreach ($roles as $r)<label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm"><input type="checkbox" class="check" name="audience_labels[]" value="{{ $r }}" :disabled="type !== 'roles'"> {{ $r }}</label>@endforeach</div>
                        <div class="flex flex-wrap gap-2" x-show="type === 'teams'">@foreach ($teams as $r)<label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm"><input type="checkbox" class="check" name="audience_labels[]" value="{{ $r }}" :disabled="type !== 'teams'"> {{ $r }}</label>@endforeach</div>
                        <div class="flex flex-wrap gap-2" x-show="type === 'projects'">@foreach ($projects as $r)<label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm"><input type="checkbox" class="check" name="audience_labels[]" value="{{ $r }}" :disabled="type !== 'projects'"> {{ $r }}</label>@endforeach</div></div>
                    <div class="flex flex-wrap gap-5 sm:col-span-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" name="notify_in_app" value="1" checked> In-app notification</label><label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" name="notify_email" value="1"> Email notification <x-demo-tag label="simulated" /></label></div>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary" :disabled="uploading">Publish</button></div>
            </form>
        </x-modal>
        @endperm
    </x-slot:modals>
</x-app-layout>
