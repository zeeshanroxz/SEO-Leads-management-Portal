@props(['title' => '', 'crumbs' => [], 'subtitle' => null, 'flush' => false])
@php
    $roles = config('portal.roles');
    $isActive = function (string $route) {
        $prefix = explode('.', $route)[0];
        if (in_array($prefix, ['finance', 'admin', 'integrations'], true) || ! str_contains($route, '.')) {
            return request()->routeIs($route);
        }
        return request()->routeIs($prefix.'.*');
    };
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('portal.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232851d6'/%3E%3Cpath d='M9 22V10h7a4 4 0 010 8h-4' stroke='white' stroke-width='3' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:shadow">Skip to content</a>

    {{-- ================= Demo bar: frontend preview only ================= --}}
    <div class="no-print relative z-50 border-b border-fuchsia-300/60 bg-fuchsia-50 text-fuchsia-900 lg:sticky lg:top-0 lg:h-[34px]">
        <div class="mx-auto flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-1.5 text-xs lg:h-[34px] lg:flex-nowrap">
            <span class="inline-flex items-center gap-1.5 font-semibold"><x-ico name="beaker" class="size-4" /> PHASE 1 · FRONTEND PROTOTYPE</span>
            <span class="hidden truncate text-fuchsia-800/80 xl:inline">Mock data only — no backend, database, email, payments or real integrations.</span>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('demo.role') }}" class="flex items-center gap-2" id="role-form">
                    @csrf
                    <label for="demo-role" class="font-medium">Preview as</label>
                    <select id="demo-role" name="role" class="h-7 rounded-md border border-fuchsia-300 bg-white px-2 text-xs text-slate-800 focus:ring-2 focus:ring-fuchsia-400" onchange="this.form.submit()">
                        @foreach ($roles as $key => $r)
                            <option value="{{ $key }}" @selected($key === $demoRoleKey)>{{ $r['label'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="back" value="{{ url()->current() }}">
                    <button type="button" class="rounded p-1 text-fuchsia-700 hover:bg-fuchsia-100" x-data
                            @click="$store.toast.push('The role switcher is a frontend preview feature only. It must never become a security mechanism - production authorisation is enforced on the server.', 'info', { ttl: 9000 })"
                            aria-label="About the role switcher"><x-ico name="information-circle" class="size-4" /></button>
                </form>
                @if ($demoRoleKey === 'finance')
                    <form method="POST" action="{{ route('demo.finance-manager') }}" class="flex items-center gap-1.5">
                        @csrf
                        <label class="flex items-center gap-1.5"><input type="checkbox" class="check size-3.5" @checked(session('demo_finance_manager', true)) onchange="this.form.submit()"> Finance Manager permissions</label>
                    </form>
                @endif
                @if ($demoDirty)
                    <form method="POST" action="{{ route('demo.reset') }}">@csrf
                        <button class="rounded-md border border-fuchsia-300 bg-white px-2 py-0.5 font-medium hover:bg-fuchsia-100" type="submit">Reset demo data</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="flex min-h-[calc(100vh-34px)]">
        {{-- ================= Sidebar ================= --}}
        <div class="no-print fixed inset-0 z-[54] bg-slate-900/50 lg:hidden" x-show="sidebar" x-cloak x-transition.opacity @click="sidebar = false"></div>
        <aside class="no-print fixed inset-y-0 left-0 z-[55] flex w-64 -translate-x-full flex-col bg-side text-slate-300 lg:top-[34px] lg:z-40 transition-transform lg:translate-x-0" :class="{ 'translate-x-0': sidebar }" aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" class="flex h-14 shrink-0 items-center gap-2.5 border-b border-side-line px-4">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white"><x-ico name="phone-arrow-up-right" class="size-5" /></span>
                <span class="leading-tight"><span class="block text-sm font-semibold text-white">Call Centre Portal</span><span class="block text-[11px] text-[#9aa8bd]">Operations &amp; Sales</span></span>
            </a>
            <nav class="scroll-thin flex-1 space-y-5 overflow-y-auto px-3 py-4">
                @foreach ($navSections as $section)
                    <div>
                        <p class="mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-wider text-[#9aa8bd]">{{ $section['label'] }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($section['items'] as $item)
                                <li>
                                    <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive($item['route']) ? 'nav-link-active' : '' }}" @if ($isActive($item['route'])) aria-current="page" @endif>
                                        <x-ico :name="$item['icon']" class="size-[18px] opacity-80" />
                                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        @if (! empty($item['badge']))<span class="rounded-full bg-brand-500 px-1.5 text-[11px] font-semibold text-white">{{ $item['badge'] }}</span>@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
            <div class="border-t border-side-line p-3">
                <div class="flex items-center gap-2.5 rounded-lg px-2 py-1.5">
                    <x-avatar :name="$demoUser->name" size="sm" />
                    <div class="min-w-0 leading-tight"><p class="truncate text-[13px] font-medium text-white">{{ $demoUser->name }}</p><p class="truncate text-[11px] text-[#9aa8bd]">{{ $demoRoleLabel }}</p></div>
                </div>
            </div>
        </aside>

        {{-- ================= Main column ================= --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            <header class="no-print sticky top-0 z-20 flex h-14 lg:top-[34px] items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
                <button type="button" class="btn btn-ghost btn-icon lg:hidden" @click="sidebar = true" aria-label="Open navigation"><x-ico name="bars-3" /></button>

                <div class="relative max-w-md flex-1" x-data="globalSearch('{{ route('search') }}')" @click.outside="open = false" @keydown.escape="open = false">
                    <label for="global-search" class="sr-only">Search</label>
                    <x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                    <input id="global-search" type="search" x-model="q" @input="onInput()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
                           class="input h-9 bg-slate-50 pl-9" placeholder="Search leads, customers, agreements…" autocomplete="off">
                    <div x-cloak x-show="open" class="absolute left-0 right-0 top-11 z-30 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <template x-if="results.length === 0"><p class="px-4 py-3 text-sm text-slate-500">No matches in your scope.</p></template>
                        <ul class="max-h-80 overflow-y-auto py-1">
                            <template x-for="(r, i) in results" :key="r.url">
                                <li><a :href="r.url" class="flex items-center justify-between gap-3 px-4 py-2 text-sm hover:bg-slate-50" :class="{ 'bg-slate-50': i === active }">
                                    <span class="min-w-0"><span class="block truncate font-medium text-slate-800" x-text="r.label"></span><span class="block truncate text-xs text-slate-500" x-text="r.sub"></span></span>
                                    <span class="tag shrink-0" x-text="r.type"></span></a></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-1">
                    @perm('leads.create')
                        <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm hidden sm:inline-flex"><x-ico name="plus" class="size-4" /> Create lead</a>
                    @endperm

                    {{-- Notifications --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button" class="btn btn-ghost btn-icon relative" @click="open = !open" aria-label="Notifications ({{ $unreadCount }} unread)" :aria-expanded="open">
                            <x-ico name="bell" />
                            @if ($unreadCount)<span class="absolute right-1 top-1 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $unreadCount }}</span>@endif
                        </button>
                        <div x-cloak x-show="open" x-transition class="absolute right-0 top-10 z-30 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl sm:w-96">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5"><p class="text-sm font-semibold">Notifications</p>
                                <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="text-xs font-medium text-brand-700 hover:underline">Mark all read</button></form></div>
                            @php($latest = app(\App\Mock\Repositories\PortalRepository::class)->notifications()->take(5))
                            <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                                @forelse ($latest as $n)
                                    <li><a href="{{ route($n->link[0], $n->link[1]) }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50">
                                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500"><x-ico :name="$n->icon" class="size-4" /></span>
                                        <span class="min-w-0"><span class="flex items-center gap-1.5 text-sm font-medium text-slate-900">{{ $n->title }} @if ($n->unread)<span class="size-2 rounded-full bg-brand-500"></span>@endif</span>
                                        <span class="line-clamp-2 text-xs text-slate-500">{{ $n->body }}</span><span class="text-[11px] text-slate-400">{{ ago($n->at) }}</span></span></a></li>
                                @empty
                                    <li class="px-4 py-6 text-center text-sm text-slate-500">You're all caught up.</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-sm font-medium text-brand-700 hover:bg-slate-50">View all notifications</a>
                        </div>
                    </div>

                    {{-- Profile --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-slate-100" @click="open = !open" :aria-expanded="open" aria-label="Account menu">
                            <x-avatar :name="$demoUser->name" size="sm" />
                            <span class="hidden text-left leading-tight md:block"><span class="block text-[13px] font-medium text-slate-800">{{ $demoUser->name }}</span><span class="block text-[11px] text-slate-500">{{ $demoRoleLabel }}</span></span>
                            <x-ico name="chevron-down" class="hidden size-4 text-slate-400 md:block" />
                        </button>
                        <div x-cloak x-show="open" x-transition class="absolute right-0 top-11 z-30 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                            <div class="border-b border-slate-100 px-4 py-2.5"><p class="text-sm font-medium">{{ $demoUser->name }}</p><p class="text-xs text-slate-500">{{ $demoUser->email }}</p></div>
                            @perm('calendar.use')<a href="{{ route('integrations.calendar') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-ico name="link" class="size-4 text-slate-400" /> Calendar connections</a>@endperm
                            <a href="{{ route('notifications.settings') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-ico name="bell-alert" class="size-4 text-slate-400" /> Notification settings</a>
                            <a href="{{ route('login') }}" class="flex items-center gap-2 border-t border-slate-100 px-4 py-2 text-sm hover:bg-slate-50"><x-ico name="arrow-right-start-on-rectangle" class="size-4 text-slate-400" /> Sign out (mock)</a>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main" class="flex-1 px-4 py-6 sm:px-6">
                <div class="mx-auto max-w-[1400px]">
                    @if ($crumbs)
                        <nav class="mb-2 text-xs text-slate-500" aria-label="Breadcrumb">
                            <ol class="flex flex-wrap items-center gap-1.5">
                                <li><a href="{{ route('dashboard') }}" class="hover:text-slate-800">Home</a></li>
                                @foreach ($crumbs as $c)
                                    <li class="flex items-center gap-1.5"><x-ico name="chevron-right" class="size-3 text-slate-300" />
                                        @if (! empty($c[1]))<a href="{{ $c[1] }}" class="hover:text-slate-800">{{ $c[0] }}</a>@else<span class="text-slate-700">{{ $c[0] }}</span>@endif</li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    @if ($title)
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ $title }}</h1>
                                @if ($subtitle)<p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $subtitle }}</p>@endif
                            </div>
                            @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
                        </div>
                    @endif
                    {{ $slot }}
                </div>
            </main>

            <footer class="no-print border-t border-slate-200 px-6 py-3 text-xs text-slate-400">
                Phase 1 prototype · all names, amounts and events are fictional sample data · simulated actions are labelled “Demo simulation”.
            </footer>
        </div>
    </div>

    {{-- ================= Toasts ================= --}}
    <div class="no-print pointer-events-none fixed bottom-4 right-4 z-[70] flex w-[min(92vw,26rem)] flex-col gap-2" aria-live="polite">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg" :class="{ 'border-emerald-200': t.type === 'success', 'border-red-200': t.type === 'error', 'border-sky-200': t.type === 'info' }" x-transition>
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-white" :class="{ 'bg-emerald-500': t.type === 'success', 'bg-red-500': t.type === 'error', 'bg-sky-500': t.type === 'info' }" x-text="t.type === 'success' ? '✓' : (t.type === 'error' ? '!' : 'i')"></span>
                <div class="min-w-0 flex-1 text-sm text-slate-800"><template x-if="t.demo"><span class="tag tag-demo mb-1">Demo simulation</span></template><p x-text="t.message"></p></div>
                <button type="button" class="text-slate-400 hover:text-slate-700" @click="$store.toast.dismiss(t.id)" aria-label="Dismiss">✕</button>
            </div>
        </template>
    </div>

    {{-- ================= Generic confirmation modal ================= --}}
    <div x-cloak x-show="$store.modal.is('confirm')" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" @keydown.escape.window="$store.modal.close()">
        <div class="absolute inset-0 bg-slate-900/50" @click="$store.modal.close()"></div>
        <form method="POST" :action="$store.modal.payload.action" class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" x-trap.noscroll.inert="$store.modal.is('confirm')"
              x-data="{ reason: '', touched: false }" @submit="if ($store.modal.payload.reason && reason.trim().length < 5) { $event.preventDefault(); touched = true }">
            @csrf
            <template x-for="(v, k) in ($store.modal.payload.fields || {})" :key="k"><input type="hidden" :name="k" :value="v"></template>
            <h2 id="confirm-title" class="text-base font-semibold text-slate-900" x-text="$store.modal.payload.title"></h2>
            <p class="mt-1.5 text-sm text-slate-600" x-text="$store.modal.payload.body"></p>
            <template x-if="$store.modal.payload.reason">
                <div class="mt-3">
                    <label class="label" for="confirm-reason">Reason (logged in the audit trail)<span class="text-red-500"> *</span></label>
                    <textarea id="confirm-reason" name="reason" x-model="reason" rows="3" class="textarea" :class="{ 'input-error': touched && reason.trim().length < 5 }" placeholder="Explain why…"></textarea>
                    <p class="err" x-show="touched && reason.trim().length < 5">A reason of at least 5 characters is required.</p>
                </div>
            </template>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button>
                <button type="submit" class="btn" :class="$store.modal.payload.danger ? 'btn-danger' : 'btn-primary'" x-text="$store.modal.payload.confirm || 'Confirm'"></button>
            </div>
        </form>
    </div>

    @isset($modals){{ $modals }}@endisset

    @if (session('toast'))
        <script>document.addEventListener('alpine:init', () => setTimeout(() => Alpine.store('toast').push(@js(session('toast.message')), @js(session('toast.type', 'success')), { demo: @js((bool) session('toast.demo', false)) }), 50));</script>
    @endif
    @if ($errors->any())
        <script>document.addEventListener('alpine:init', () => setTimeout(() => Alpine.store('toast').push(@js($errors->first()), 'error'), 50));</script>
    @endif
</body>
</html>
