<?php

namespace App\Support;

use App\Mock\Record;
use App\Mock\Repositories\PortalRepository;

/**
 * Record-level / field-group visibility overrides (UI simulation).
 * Phase 2: enforced by server-side policies and omitted from API responses.
 */
class Visibility
{
    /** Roles that are "original deal owners" for upsell restrictions. */
    private const OWNER_ROLES = ['agent', 'team_lead', 'manager'];

    public static function restricted(Record|string $lead, string $group): bool
    {
        $id = $lead instanceof Record ? $lead->id : $lead;
        if (! in_array(Demo::roleKey(), self::OWNER_ROLES, true)) {
            return false;
        }

        return app(PortalRepository::class)->overrides()
            ->contains(fn ($o) => $o->lead_id === $id && $o->group_key === $group && $o->effect === 'restricted' && $o->status === 'active');
    }

    public static function granted(Record|string $lead, string $group): bool
    {
        $id = $lead instanceof Record ? $lead->id : $lead;

        return app(PortalRepository::class)->overrides()
            ->contains(fn ($o) => $o->lead_id === $id && $o->group_key === $group && $o->effect === 'granted' && $o->status === 'active');
    }
}
