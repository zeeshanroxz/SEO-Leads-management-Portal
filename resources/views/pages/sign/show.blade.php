@php
    $cust = $a->signers[0];
    $state = match (true) {
        $cust->signed_at !== null => 'signed',
        in_array($a->status, ['sent', 'viewed', 'partially_signed']) => 'active',
        in_array($a->status, ['draft', 'ready_to_send']) => 'inactive',
        default => 'closed',
    };
    $cfg = ['signed' => $state === 'signed', 'signerName' => $cust->name, 'demoCode' => '123456'];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Review &amp; sign · {{ $a->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-canvas" x-data>
<div class="border-b border-fuchsia-300/60 bg-fuchsia-50 px-4 py-2 text-center text-xs text-fuchsia-900"><strong>DEMO CUSTOMER SIGNING PAGE.</strong> This mockup does not store a signature, send any code or create anything legally valid. <a href="{{ route('agreements.show', $a->id) }}" class="ml-2 underline">Back to the portal</a></div>
<header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-4xl items-center gap-3 px-4 py-4"><span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 font-bold text-white">CC</span><div class="leading-tight"><p class="font-semibold">Call Centre Digital Services</p><p class="text-xs text-slate-500">Secure agreement signing (sample)</p></div><span class="ml-auto hidden items-center gap-1.5 text-xs text-slate-500 sm:flex"><x-ico name="lock-closed" class="size-4" /> Secure unique link (demo)</span></div></header>

<main class="mx-auto max-w-4xl px-4 py-8" x-data="signingFlow(@js($cfg))">
    @if ($state === 'inactive' || $state === 'closed')
        <div class="card mx-auto max-w-lg"><x-empty-state :icon="$state === 'inactive' ? 'clock' : 'no-symbol'" :title="$state === 'inactive' ? 'This agreement is not ready for signing yet' : 'This agreement is no longer available'" :text="$state === 'inactive' ? 'The provider has not sent it yet. Please check your email for the secure link.' : 'It is '.strtolower(status_label('agreement', $a->status)).'. Contact your account manager if you think this is a mistake.'" /></div>
    @else
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">Agreement for {{ $customer->business_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Reference {{ $a->id }} · prepared for {{ $cust->name }} ({{ $cust->email }})</p>
        </div>

        <ol class="mb-6 flex items-center gap-2 text-sm" aria-label="Progress">
            <template x-for="(s, i) in ['Review', 'Verify', 'Sign', 'Done']" :key="s"><li class="flex flex-1 items-center gap-2"><span class="flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-500')" x-text="step > i + 1 ? '✓' : i + 1"></span><span :class="step === i + 1 ? 'font-medium text-slate-900' : 'text-slate-500'" x-text="s"></span><span class="h-px flex-1 bg-slate-200" x-show="i < 3"></span></li></template>
        </ol>

        {{-- STEP 1 review --}}
        <section x-show="step === 1" class="space-y-5">
            <x-agreement-doc :a="$a" />
            <div class="card card-pad space-y-3">
                <label class="flex items-start gap-3 text-sm"><input type="checkbox" class="check mt-0.5" x-model="read"> I have read the full agreement above.</label>
                <div class="flex flex-wrap items-center justify-between gap-3"><button type="button" class="text-sm font-medium text-brand-700 hover:underline" @click="offline = !offline">Prefer to sign on paper?</button><button type="button" class="btn btn-primary" :disabled="!read" @click="next()">Continue to verification</button></div>
                <div x-show="offline" x-cloak class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="font-medium">Offline signing (alternative)</p><ol class="mt-1 list-decimal pl-5 text-slate-600"><li>Download or print the agreement <button type="button" class="font-medium text-brand-700 underline" @click="window.print()">(print / save as PDF)</button>.</li><li>Sign it and take a clear scan or photo.</li><li>Upload the signed copy — our Finance team reviews it before the agreement is marked signed.</li></ol>
                    <form method="POST" action="{{ route('sign.complete', $a->id) }}" class="mt-3 flex flex-wrap items-center gap-3" @submit="if (!file) $event.preventDefault()">@csrf<input type="hidden" name="mode" value="offline"><input type="hidden" name="file_name" :value="file">
                        <input type="file" accept=".pdf,image/*" class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium" @change="file = $event.target.files[0]?.name ?? ''"><button class="btn btn-secondary btn-sm" :disabled="!file">Upload signed copy (demo)</button></form>
                    @if ($a->offline_upload)<p class="mt-2 text-xs text-emerald-700">Received: {{ $a->offline_upload->name }} — awaiting Finance review.</p>@endif
                </div>
            </div>
        </section>

        {{-- STEP 2 verify --}}
        <section x-show="step === 2" x-cloak class="card card-pad mx-auto max-w-md">
            <h2 class="text-lg font-semibold">Verify it's you</h2>
            <p class="mt-1 text-sm text-slate-600">We “sent” a 6-digit one-time code to <strong>{{ $cust->email }}</strong>.</p>
            <div class="mt-3 rounded-lg bg-fuchsia-50 p-3 text-xs text-fuchsia-900"><strong>Demo:</strong> no email is sent. Use code <span class="font-mono font-bold">123456</span>.</div>
            <div class="mt-4"><label class="label" for="code">One-time code</label><input id="code" inputmode="numeric" maxlength="6" x-model="code" @keydown.enter.prevent="verify()" class="input text-center font-mono text-lg tracking-[0.4em]" :class="{ 'input-error': codeError }" placeholder="••••••"><p class="err" x-text="codeError" x-show="codeError" x-cloak></p></div>
            <div class="mt-4 flex items-center justify-between"><button type="button" class="text-sm font-medium text-brand-700 hover:underline" @click="sendCode()">Send a new code</button><div class="flex gap-2"><button type="button" class="btn btn-secondary" @click="step = 1">Back</button><button type="button" class="btn btn-primary" :disabled="code.length < 6" @click="verify()">Verify</button></div></div>
        </section>

        {{-- STEP 3 sign --}}
        <section x-show="step === 3" x-cloak x-effect="if (step === 3 && method === 'drawn') $nextTick(() => initPad())">
            <form method="POST" action="{{ route('sign.complete', $a->id) }}" @submit="submit($event)" class="card card-pad mx-auto max-w-2xl space-y-5">
                @csrf <input type="hidden" name="method" :value="method">
                <div><h2 class="text-lg font-semibold">Sign the agreement</h2><p class="text-sm text-slate-600">Choose how to add your signature.</p></div>
                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5" role="tablist"><button type="button" role="tab" class="rounded-md px-4 py-1.5 text-sm font-medium" :class="method === 'typed' ? 'bg-brand-600 text-white' : 'text-slate-600'" @click="method = 'typed'">Type my name</button><button type="button" role="tab" class="rounded-md px-4 py-1.5 text-sm font-medium" :class="method === 'drawn' ? 'bg-brand-600 text-white' : 'text-slate-600'" @click="method = 'drawn'; $nextTick(() => initPad())">Draw</button></div>
                <div><label class="label" for="sname">Full legal name <span class="text-red-500">*</span></label><input id="sname" name="name" x-model="name" class="input" :class="{ 'input-error': touched && name.trim().length < 2 }"><p class="err" x-show="touched && name.trim().length < 2" x-cloak>Enter your full name.</p></div>
                <div x-show="method === 'typed'"><p class="label">Signature preview</p><div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-5 text-3xl text-slate-800" style="font-family: 'Brush Script MT', 'Segoe Script', cursive" x-text="name || 'Your signature'"></div></div>
                <div x-show="method === 'drawn'" x-cloak><p class="label">Draw your signature</p><div class="relative"><canvas x-ref="pad" class="sig-canvas h-40 w-full rounded-lg border border-dashed border-slate-300 bg-slate-50" aria-label="Signature drawing area"></canvas><button type="button" class="btn btn-secondary btn-xs absolute right-2 top-2" @click="clearPad()">Clear</button></div><p class="err" x-show="touched && method === 'drawn' && !hasInk" x-cloak>Please draw your signature above.</p></div>
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" class="check mt-0.5" name="consent" value="1" x-model="consent"> <span>I agree to sign electronically and that my typed or drawn signature is my intent to be bound by this agreement. <span class="block text-xs text-slate-400">(Consent wording is a placeholder — legal review required before production.)</span></span></label>
                <p class="err" x-show="touched && !consent" x-cloak>Please tick the consent box.</p>
                @error('consent')<p class="err">{{ $message }}</p>@enderror
                <div class="flex justify-between"><button type="button" class="btn btn-secondary" @click="step = 2">Back</button><button class="btn btn-primary"><x-ico name="pencil-square" class="size-4" /> Sign agreement (demo)</button></div>
            </form>
        </section>

        {{-- STEP 4 done --}}
        <section x-show="step === 4" x-cloak class="space-y-5">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5"><div class="flex items-start gap-3"><x-ico name="check-circle" class="mt-0.5 size-6 text-emerald-600" /><div><h2 class="text-lg font-semibold text-emerald-900">Thank you — demo signature recorded</h2><p class="mt-1 text-sm text-emerald-900">@if ($cust->signed_at)Signed by {{ $cust->name }} on {{ fdt($cust->signed_at) }}. @endif{{ $a->status === 'signed' ? 'Both parties have signed.' : 'The provider will counter-sign shortly.' }} <strong>No legally valid signature has been stored — this is a prototype.</strong></p></div></div></div>
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div><p class="mb-2 text-sm font-medium text-slate-700">Final document preview</p><x-agreement-doc :a="$a" /></div>
                <div class="space-y-4"><div class="card card-pad text-sm"><p class="font-semibold">Receipt (demo)</p><dl class="mt-2 space-y-1.5 text-xs text-slate-600"><div class="flex justify-between"><dt>Agreement</dt><dd>{{ $a->id }} v{{ $a->version }}</dd></div><div class="flex justify-between"><dt>Signer</dt><dd>{{ $cust->name }}</dd></div><div class="flex justify-between"><dt>Method</dt><dd>{{ ucfirst($cust->method ?? '—') }}</dd></div><div class="flex justify-between"><dt>Verification</dt><dd>{{ $cust->verification }}</dd></div><div class="flex justify-between"><dt>Time</dt><dd>{{ fdt($cust->signed_at) }}</dd></div></dl>
                    <button type="button" class="btn btn-secondary mt-4 w-full" onclick="window.print()"><x-ico name="arrow-down-tray" class="size-4" /> Download PDF (print)</button></div></div>
            </div>
        </section>
    @endif
</main>

@if (session('toast'))<script>document.addEventListener('alpine:init', () => setTimeout(() => Alpine.store('toast').push(@js(session('toast.message')), 'success', { demo: true }), 50));</script>@endif
<div class="pointer-events-none fixed bottom-4 right-4 z-[70] flex w-[min(92vw,26rem)] flex-col gap-2" aria-live="polite">
    <template x-for="t in $store.toast.items" :key="t.id"><div class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg" :class="t.type === 'error' ? 'border-red-200' : 'border-emerald-200'"><div class="min-w-0 flex-1 text-sm text-slate-800"><template x-if="t.demo"><span class="tag tag-demo mb-1">Demo simulation</span></template><p x-text="t.message"></p></div><button type="button" class="text-slate-400" @click="$store.toast.dismiss(t.id)" aria-label="Dismiss">✕</button></div></template>
</div>
</body>
</html>
