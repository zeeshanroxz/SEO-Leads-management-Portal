<?php

namespace App\Support;

use App\Mock\DemoStore;

/**
 * Admin-configurable status lists (Phase 1: config/statuses.php + session overlay).
 * Custom statuses and "disabled" flags let the prototype demonstrate Admin
 * status configuration without a database.
 */
class Statuses
{
    /** @return array<string, array{0:string,1:string,2:array}> key => [label, colour, flags] */
    public static function all(string $dimension): array
    {
        $base = config("statuses.$dimension.values", []);
        $custom = DemoStore::flag('status_custom', [])[$dimension] ?? [];
        foreach ($custom as $key => $def) {
            $base[$key] = [$def['label'], $def['color'], ['reason' => (bool) ($def['reason'] ?? false), 'custom' => true]];
        }

        return $base;
    }

    public static function disabled(string $dimension): array
    {
        return array_keys(array_filter(DemoStore::flag('status_disabled', [])[$dimension] ?? []));
    }

    /** Statuses a user may currently pick (active only). */
    public static function options(string $dimension): array
    {
        $disabled = self::disabled($dimension);

        return array_filter(self::all($dimension), fn ($v, $k) => ! in_array($k, $disabled, true), ARRAY_FILTER_USE_BOTH);
    }

    public static function add(string $dimension, string $label, string $color, bool $reason): string
    {
        $key = 'custom_'.preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
        $custom = DemoStore::flag('status_custom', []);
        $custom[$dimension][$key] = ['label' => $label, 'color' => $color, 'reason' => $reason];
        DemoStore::setFlag('status_custom', $custom);

        return $key;
    }

    public static function toggle(string $dimension, string $key): bool
    {
        $d = DemoStore::flag('status_disabled', []);
        $d[$dimension][$key] = ! ($d[$dimension][$key] ?? false);
        DemoStore::setFlag('status_disabled', $d);

        return $d[$dimension][$key];
    }
}
