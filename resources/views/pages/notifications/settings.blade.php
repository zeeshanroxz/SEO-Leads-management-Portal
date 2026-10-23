<x-app-layout title="Notification settings" :crumbs="[['Notifications', route('notifications.index')], ['Settings']]" subtitle="Choose how you want to hear about activity. Email is simulated in Phase 1.">
    <form method="POST" action="{{ route('notifications.settings.save') }}" class="space-y-5">
        @csrf
        <div class="card overflow-hidden">
            <table class="tbl">
                <thead><tr><th>Notification</th><th class="w-28 text-center">In-app</th><th class="w-28 text-center">Email <x-demo-tag label="sim" /></th></tr></thead>
                <tbody>
                    @foreach ($prefs as $key => $p)
                        <tr>
                            <td><p class="font-medium text-slate-900">{{ $p[0] }}</p><p class="text-xs text-slate-500">{{ $p[1] }}</p></td>
                            <td class="text-center"><input type="checkbox" class="check" name="prefs[{{ $key }}][in_app]" value="1" @checked($saved[$key]['in_app'] ?? true) aria-label="In-app for {{ $p[0] }}"></td>
                            <td class="text-center"><input type="checkbox" class="check" name="prefs[{{ $key }}][email]" value="1" @checked($saved[$key]['email'] ?? in_array($key, ['appointment', 'review_queue'])) aria-label="Email for {{ $p[0] }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @perm('calendar.use')
        <div class="card card-pad grid gap-4 sm:grid-cols-2">
            <x-field label="Appointment reminder lead time" name="reminder_minutes" help="Prototype default - reminder timing policy is an open decision (see Assumption register).">
                <select name="reminder_minutes" id="reminder_minutes" class="select">
                    @foreach ([15, 30, 60, 120, 1440] as $m)<option value="{{ $m }}" @selected((int) app(\App\Mock\Repositories\PortalRepository::class)->setting('notif_reminder_minutes', 30) === $m)>{{ $m >= 60 ? ($m / 60).' hour'.($m > 60 ? 's' : '') : $m.' minutes' }} before</option>@endforeach
                </select>
            </x-field>
        </div>
        @endperm
        <div class="flex justify-end"><button class="btn btn-primary">Save preferences</button></div>
    </form>
</x-app-layout>
