<?php

namespace App\Support;

use App\Mock\DemoStore;
use App\Mock\Record;
use App\Mock\Repositories\DirectoryRepository;

/**
 * Demo-only "who am I" helper.
 *
 * !! The role switcher is a frontend preview feature. It must NEVER become a
 * !! security mechanism - Phase 2 enforces authorisation on the server.
 */
class Demo
{
    public static function roleKey(): string
    {
        $role = session('demo_role', 'admin');

        return array_key_exists($role, config('portal.roles')) ? $role : 'admin';
    }

    public static function roleLabel(?string $role = null): string
    {
        return config('portal.roles.'.($role ?? self::roleKey()).'.label');
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::roleKey(), $roles, true);
    }

    public static function user(): Record
    {
        return app(DirectoryRepository::class)->user(config('portal.roles.'.self::roleKey().'.user'));
    }

    public static function id(): string
    {
        return self::user()->id;
    }

    /** Finance Manager is an extra permission set inside the Finance role. */
    public static function financeManager(): bool
    {
        return self::is('finance') && (bool) session('demo_finance_manager', true);
    }

    /** @param string|array $permission any-of when an array */
    public static function can(string|array $permission): bool
    {
        $granted = config('portal.permissions.'.self::roleKey(), []);
        if (in_array('*', $granted, true)) {
            return true;
        }
        foreach ((array) $permission as $p) {
            if ($p === 'commission.approve_manager') {
                if (self::financeManager()) {
                    return true;
                }

                continue;
            }
            if (in_array($p, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    public static function dirty(): bool
    {
        return DemoStore::isDirty();
    }
}
