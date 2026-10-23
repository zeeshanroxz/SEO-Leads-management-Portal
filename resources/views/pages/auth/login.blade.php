<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('portal.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-canvas">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
    <div class="flex flex-col justify-center px-6 py-10 sm:px-12 lg:px-16">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white"><x-ico name="phone-arrow-up-right" class="size-6" /></span>
                <div class="leading-tight"><p class="text-base font-semibold text-slate-900">Call Centre Portal</p><p class="text-xs text-slate-500">CRM · Audit · Agreements · Commission</p></div>
            </div>
            <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
            <p class="mt-1 text-sm text-slate-500">Use your work account to continue.</p>

            <div class="mt-4 flex items-start gap-2 rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-3 text-xs text-fuchsia-900">
                <x-ico name="beaker" class="mt-0.5 size-4" />
                <p><strong>Login mockup.</strong> No authentication exists in Phase 1 — the form below is not validated against anything. Pick a demo role on the right to preview each experience.</p>
            </div>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('demo.role') }}" x-data="{ email: '', pw: '', touched: false }" @submit="touched = true; if (!email || !pw) $event.preventDefault()">
                @csrf
                <input type="hidden" name="role" value="admin"><input type="hidden" name="go_dashboard" value="1">
                <x-field label="Work email" name="email" :error="null">
                    <input id="email" type="email" x-model="email" class="input" :class="{ 'input-error': touched && !email }" placeholder="you@callcentre.example" autocomplete="username">
                    <p class="err" x-show="touched && !email" x-cloak>Enter your work email.</p>
                </x-field>
                <x-field label="Password" name="password">
                    <input id="password" type="password" x-model="pw" class="input" :class="{ 'input-error': touched && !pw }" placeholder="••••••••" autocomplete="current-password">
                    <p class="err" x-show="touched && !pw" x-cloak>Enter your password.</p>
                </x-field>
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" class="check"> Keep me signed in</label>
                    <button type="button" class="font-medium text-brand-700 hover:underline" @click="$store.toast.push('Password reset is not part of the frontend prototype.', 'info', { demo: true })">Forgot password?</button>
                </div>
                <button class="btn btn-primary h-10 w-full">Sign in <x-demo-tag label="mock" /></button>
            </form>
            <p class="mt-6 text-xs text-slate-400">Protected internal system. Activity is logged. (Production: MFA, session security and account deactivation arrive in Phase 2.)</p>
        </div>
    </div>

    <div class="flex flex-col justify-center bg-side px-6 py-10 sm:px-12">
        <div class="mx-auto w-full max-w-xl">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#9aa8bd]">Demo mode</p>
            <h2 class="mt-1 text-xl font-semibold text-white">Continue as…</h2>
            <p class="mt-1 text-sm text-[#9aa8bd]">Each role sees different screens, columns and actions. This picker is a frontend preview feature and must never become a security mechanism.</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($roles as $key => $r)
                    <form method="POST" action="{{ route('demo.role') }}">
                        @csrf
                        <input type="hidden" name="role" value="{{ $key }}"><input type="hidden" name="go_dashboard" value="1">
                        <button class="group flex w-full items-start gap-3 rounded-xl border border-side-line bg-side-hover p-4 text-left transition hover:border-brand-400 hover:bg-[#223049]">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-600/20 text-brand-300">
                                <x-ico :name="['admin' => 'shield-check', 'finance' => 'banknotes', 'auditor' => 'document-magnifying-glass', 'manager' => 'user-group', 'team_lead' => 'flag', 'agent' => 'phone'][$key]" class="size-5" />
                            </span>
                            <span><span class="block text-sm font-semibold text-white">{{ $r['label'] }}</span><span class="mt-0.5 block text-xs text-[#9aa8bd]">{{ $r['blurb'] }}</span></span>
                        </button>
                    </form>
                @endforeach
            </div>
            <p class="mt-6 text-xs text-[#9aa8bd]">All data shown after this point is fictional mock data.</p>
        </div>
    </div>
</div>
</body>
</html>
