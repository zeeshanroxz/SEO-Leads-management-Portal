<?php

namespace App\Providers;

use App\Contracts;
use App\Mock\Repositories;
use App\Support\Demo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Phase 1: every data contract is bound to its session-backed MOCK adapter.
     * Phase 2 swaps these bindings for Eloquent-backed implementations.
     */
    public function register(): void
    {
        $bindings = [
            Contracts\DirectoryRepositoryContract::class => Repositories\DirectoryRepository::class,
            Contracts\LeadRepositoryContract::class => Repositories\LeadRepository::class,
            Contracts\FormRepositoryContract::class => Repositories\FormRepository::class,
            Contracts\AuditRepositoryContract::class => Repositories\AuditRepository::class,
            Contracts\CalendarRepositoryContract::class => Repositories\CalendarRepository::class,
            Contracts\AgreementRepositoryContract::class => Repositories\AgreementRepository::class,
            Contracts\FinanceRepositoryContract::class => Repositories\FinanceRepository::class,
            Contracts\PortalRepositoryContract::class => Repositories\PortalRepository::class,
        ];

        foreach ($bindings as $contract => $mock) {
            $this->app->singleton($mock);
            $this->app->alias($mock, $contract);
        }
    }

    public function boot(): void
    {
        // @perm('leads.review') ... @endperm   (demo-role UI gating only)
        Blade::if('perm', fn (...$p) => Demo::can(count($p) === 1 ? $p[0] : $p));
        Blade::if('role', fn (...$roles) => Demo::is(...$roles));

        View::composer(['components.app-layout'], function ($view) {
            $portal = app(Repositories\PortalRepository::class);
            $view->with([
                'demoRoleKey' => Demo::roleKey(),
                'demoRoleLabel' => Demo::roleLabel(),
                'demoUser' => Demo::user(),
                'unreadCount' => $portal->unreadCount(),
                'navSections' => $this->navigation($portal),
                'demoDirty' => Demo::dirty(),
            ]);
        });
    }

    /** Sidebar filtered by the demo role's permissions, with live badge counts. */
    private function navigation(Repositories\PortalRepository $portal): array
    {
        $leads = app(Repositories\LeadRepository::class);
        $audits = app(Repositories\AuditRepository::class);
        $badges = [
            'review' => fn () => $leads->reviewQueue()->count(),
            'audits' => fn () => $audits->scoped()->whereIn('status', ['requested', 'assigned', 'revision_requested'])->count(),
            'handoff' => fn () => $leads->closedWon()->filter(fn ($l) => ($l->handoff?->state ?? null) === 'new')->count(),
        ];

        $sections = [];
        foreach (config('portal.nav') as $section) {
            $items = [];
            foreach ($section['items'] as $item) {
                [$label, $route, $icon, $perm] = $item;
                if ($perm !== null && ! Demo::can($perm)) {
                    continue;
                }
                $items[] = [
                    'label' => $label, 'route' => $route, 'icon' => $icon,
                    'badge' => isset($item[4]) ? ($badges[$item[4]])() : null,
                ];
            }
            if ($items) {
                $sections[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $sections;
    }
}
