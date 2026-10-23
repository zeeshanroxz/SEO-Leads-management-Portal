@props(['a', 'watermark' => true])
<article {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-slate-300 bg-white p-8 shadow-sm sm:p-10']) }}>
    @if ($watermark)<div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true"><span class="-rotate-12 select-none text-6xl font-black uppercase tracking-widest text-slate-100 sm:text-8xl">Demo</span></div>@endif
    <div class="relative">
        <header class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Services Agreement</p><h2 class="mt-1 text-xl font-semibold">{{ $a->customer?->business_name }}</h2><p class="text-sm text-slate-500">Reference {{ $a->id }} · Version {{ $a->version }}</p></div>
            <div class="text-right text-sm text-slate-500"><p class="font-semibold text-slate-800">Call Centre Digital Services</p><p>(sample company — fictional)</p></div>
        </header>
        <section class="mt-5 text-sm leading-relaxed text-slate-700">
            <p>This agreement is between <strong>Call Centre Digital Services</strong> (the “Provider”) and <strong>{{ $a->customer?->business_name }}</strong> (the “Customer”), represented by {{ $a->signers[0]->name ?? 'the Customer signer' }}.</p>
            <h3 class="mt-5 text-sm font-semibold text-slate-900">1. Services and fees</h3>
            <table class="mt-2 w-full border border-slate-200 text-sm"><thead class="bg-slate-50 text-left"><tr><th class="border-b border-slate-200 px-3 py-2">Service</th><th class="border-b border-slate-200 px-3 py-2">Billing</th><th class="border-b border-slate-200 px-3 py-2 text-right">Qty</th><th class="border-b border-slate-200 px-3 py-2 text-right">Price ({{ $a->currency }})</th></tr></thead>
                <tbody>@foreach ($a->lines as $l)<tr><td class="border-b border-slate-100 px-3 py-2">{{ $l->name }}</td><td class="border-b border-slate-100 px-3 py-2">{{ $l->billing === 'recurring' ? 'Monthly recurring' : 'One-time' }}</td><td class="border-b border-slate-100 px-3 py-2 text-right">{{ $l->qty }}</td><td class="border-b border-slate-100 px-3 py-2 text-right">{{ money($l->price, $a->currency) }}</td></tr>@endforeach</tbody>
                <tfoot><tr><td colspan="3" class="px-3 py-2 text-right font-medium">Recurring per month</td><td class="px-3 py-2 text-right font-semibold">{{ money($a->mrr, $a->currency) }}</td></tr><tr><td colspan="3" class="px-3 py-2 text-right font-medium">One-time total</td><td class="px-3 py-2 text-right font-semibold">{{ money($a->one_time_total, $a->currency) }}</td></tr></tfoot></table>
            <h3 class="mt-5 text-sm font-semibold text-slate-900">2. Term</h3>
            <p class="mt-1">Initial term of <strong>{{ $a->term_months }} months</strong>{{ $a->start_date ? ' starting '.fdate($a->start_date, 'd F Y') : '' }}{{ $a->end_date ? ' and ending '.fdate($a->end_date, 'd F Y') : '' }}. {{ $a->renewal }}</p>
            <h3 class="mt-5 text-sm font-semibold text-slate-900">3. Payment schedule</h3><p class="mt-1">{{ $a->payment_schedule ?: 'As set out in the invoice schedule.' }}</p>
            @foreach ($a->clauses as $i => $c)
                <h3 class="mt-5 text-sm font-semibold text-slate-900">{{ $i + 4 }}. {{ $c->title }} <span class="ml-1 text-[11px] font-normal text-slate-400">clause v{{ $c->version }}</span></h3><p class="mt-1">{{ $c->body }}</p>
            @endforeach
        </section>
        <footer class="mt-8 grid gap-6 border-t border-slate-200 pt-6 sm:grid-cols-2">
            @foreach ($a->signers as $s)
                <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $s->role }}</p>
                    @if ($s->signed_at)<p class="mt-2 border-b border-slate-300 pb-1 text-2xl text-slate-800" style="font-family: 'Brush Script MT', 'Segoe Script', cursive">{{ $s->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $s->name }} · signed {{ fdt($s->signed_at) }} ({{ $s->method }}) — DEMO</p>
                    @else<p class="mt-2 border-b border-dashed border-slate-300 pb-1 text-sm italic text-slate-400">Awaiting signature</p><p class="mt-1 text-xs text-slate-500">{{ $s->name }}</p>@endif</div>
            @endforeach
        </footer>
        <p class="mt-6 text-[11px] text-slate-400">Prototype document. Clause wording is placeholder text, not legal advice. Signature capture shown here has no legal effect.</p>
    </div>
</article>
