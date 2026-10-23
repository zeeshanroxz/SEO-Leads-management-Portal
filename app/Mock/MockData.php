<?php

namespace App\Mock;

use Carbon\Carbon;

/**
 * Loads the static, obviously-fictional seed arrays in app/Mock/Data.
 * Dates are relative to "now" so the prototype always looks current.
 */
class MockData
{
    private static array $cache = [];

    public static function get(string $name): array
    {
        return self::$cache[$name] ??= require __DIR__.'/Data/'.$name.'.php';
    }

    /** Date n days from today (negative = past), Y-m-d. */
    public static function d(int $days): string
    {
        return Carbon::today()->addDays($days)->toDateString();
    }

    /** Datetime n days from today at HH:MM, Y-m-d H:i:s. */
    public static function dt(int $days, string $time = '09:00'): string
    {
        return Carbon::today()->addDays($days)->setTimeFromTimeString($time)->toDateTimeString();
    }
}
