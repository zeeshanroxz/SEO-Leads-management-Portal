<?php

namespace App\Mock;

/**
 * Session-backed overlay on top of the static mock data.
 *
 * This is NOT a database and NOT a backend: it only lets the prototype feel
 * alive between page loads (create a lead, accept it, post a note ...).
 * "Reset demo data" in the demo bar clears it. Phase 2 replaces the mock
 * repositories that use it with Eloquent-backed ones.
 */
class DemoStore
{
    private const KEY = 'demo_state';

    private static function state(): array
    {
        return session(self::KEY, ['patch' => [], 'append' => [], 'created' => [], 'seq' => [], 'flags' => []]);
    }

    private static function save(array $state): void
    {
        session([self::KEY => $state]);
    }

    public static function patch(string $collection, string $id, array $fields): void
    {
        $s = self::state();
        $s['patch'][$collection][$id] = array_merge($s['patch'][$collection][$id] ?? [], $fields);
        self::save($s);
    }

    public static function append(string $collection, string $id, string $list, array $item): void
    {
        $s = self::state();
        $s['append'][$collection][$id][$list][] = $item;
        self::save($s);
    }

    /** Create a new row, generating an id from $prefix when none is given. */
    public static function create(string $collection, array $row, string $prefix = 'X-', int $start = 9000): array
    {
        $s = self::state();
        if (empty($row['id'])) {
            $s['seq'][$collection] = ($s['seq'][$collection] ?? $start) + 1;
            $row['id'] = $prefix.$s['seq'][$collection];
        }
        $s['created'][$collection][] = $row;
        self::save($s);

        return $row;
    }

    public static function patches(string $collection): array
    {
        return self::state()['patch'][$collection] ?? [];
    }

    public static function appends(string $collection): array
    {
        return self::state()['append'][$collection] ?? [];
    }

    public static function createdRows(string $collection): array
    {
        return self::state()['created'][$collection] ?? [];
    }

    public static function flag(string $key, mixed $default = null): mixed
    {
        return self::state()['flags'][$key] ?? $default;
    }

    public static function setFlag(string $key, mixed $value): void
    {
        $s = self::state();
        $s['flags'][$key] = $value;
        self::save($s);
    }

    public static function reset(): void
    {
        session()->forget(self::KEY);
    }

    public static function isDirty(): bool
    {
        $s = self::state();

        return ! empty($s['patch']) || ! empty($s['append']) || ! empty($s['created']) || ! empty($s['flags']);
    }
}
