<x-app-layout title="Customers" :crumbs="[['Customers']]" subtitle="Businesses and the people who work there. One customer can have many contacts, opportunities and agreements.">
    <div class="card" x-data="dataTable({ perPage: 10, sortKey: 'name', sortDir: 'asc' })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1"><label for="c-search" class="sr-only">Search customers</label><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input id="c-search" type="search" x-model="q" class="input pl-9" placeholder="Search business, contact, email…"></div>
        </div>
        @if ($customers->isEmpty())
            <x-empty-state icon="building-office-2" title="No customers in your scope" />
        @else
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th class="th-sort" @click="sort('name')">Business <span x-text="arrow('name')"></span></th><th>Primary contact</th><th>Industry</th><th class="text-right">Contacts</th><th class="text-right th-sort" @click="sort('opps')">Opportunities <span x-text="arrow('opps')"></span></th><th class="th-sort" @click="sort('since')">Customer since <span x-text="arrow('since')"></span></th><th class="w-10"></th></tr></thead>
            <tbody>
                @foreach ($customers as $c)
                    <tr data-row data-search="{{ strtolower($c->business_name.' '.collect($c->contacts)->map(fn ($k) => $k->name.' '.$k->email)->implode(' ').' '.$c->business_email) }}" data-s-name="{{ strtolower($c->business_name) }}" data-s-opps="{{ $c->lead_count }}" data-s-since="{{ $c->created_at }}">
                        <td><a href="{{ route('customers.show', $c->id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $c->business_name }}</a><p class="text-xs text-slate-500">{{ $c->id }} · {{ $c->business_email }}</p></td>
                        <td>{{ $c->primary?->name }}<p class="text-xs text-slate-500">{{ $c->primary?->designation }}</p></td>
                        <td class="text-slate-600">{{ $c->industry }}</td>
                        <td class="text-right">{{ count($c->contacts) }}</td><td class="text-right">{{ $c->lead_count }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ fdate($c->created_at) }}</td>
                        <td><a href="{{ route('customers.show', $c->id) }}" class="btn btn-ghost btn-icon" aria-label="Open {{ $c->business_name }}"><x-ico name="chevron-right" class="size-4" /></a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
        @endif
    </div>
</x-app-layout>
