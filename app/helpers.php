<?php

use App\Mock\DemoStore;
use App\Support\Demo;
use App\Support\Statuses;
use Carbon\Carbon;

if (! function_exists('fdate')) {
    function fdate($value, string $format = 'd M Y'): string
    {
        return $value ? Carbon::parse($value)->format($format) : '—';
    }
}

if (! function_exists('fdt')) {
    function fdt($value): string
    {
        return $value ? Carbon::parse($value)->format('d M Y, H:i') : '—';
    }
}

if (! function_exists('ago')) {
    function ago($value): string
    {
        return $value ? Carbon::parse($value)->diffForHumans() : '—';
    }
}

if (! function_exists('money')) {
    function money($amount, ?string $currency = null, int $decimals = 0): string
    {
        $currency ??= DemoStore::flag('currency', config('portal.currency', 'USD'));
        $symbols = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'CAD' => 'CA$', 'AUD' => 'A$'];
        $symbol = $symbols[$currency] ?? $currency.' ';
        $n = (float) $amount;

        return ($n < 0 ? '-' : '').$symbol.number_format(abs($n), $decimals);
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name));

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}

if (! function_exists('status_meta')) {
    /** @return array{0:string,1:string,2:array} label, colour, flags */
    function status_meta(string $dimension, ?string $key): array
    {
        $def = config("statuses.$dimension.values.$key") ?? (Statuses::all($dimension)[$key] ?? null);
        if (! $def) {
            return [ucwords(str_replace('_', ' ', (string) $key)) ?: '—', 'slate', []];
        }

        return [$def[0], $def[1], $def[2] ?? []];
    }
}

if (! function_exists('status_label')) {
    function status_label(string $dimension, ?string $key): string
    {
        return status_meta($dimension, $key)[0];
    }
}

if (! function_exists('demo')) {
    function demo(): string
    {
        return Demo::class;
    }
}

if (! function_exists('can_do')) {
    function can_do(string|array $permission): bool
    {
        return Demo::can($permission);
    }
}
