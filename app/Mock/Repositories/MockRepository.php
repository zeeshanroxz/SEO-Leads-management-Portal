<?php

namespace App\Mock\Repositories;

use App\Mock\DemoStore;
use App\Mock\MockData;
use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Base for all mock repository adapters: static seed data + session overlay,
 * returned as Records so Blade code is Eloquent-shaped.
 */
abstract class MockRepository
{
    /** @return Collection<int, Record> */
    protected function rows(string $name, ?string $overlay = null): Collection
    {
        $overlay ??= $name;
        $base = MockData::get($name);
        $created = DemoStore::createdRows($overlay);
        $patches = DemoStore::patches($overlay);
        $appends = DemoStore::appends($overlay);

        return collect(array_merge($base, $created))->map(function (array $row) use ($patches, $appends) {
            $id = $row['id'] ?? null;
            if ($id && isset($patches[$id])) {
                $row = array_merge($row, $patches[$id]);
            }
            if ($id && isset($appends[$id])) {
                foreach ($appends[$id] as $list => $items) {
                    $row[$list] = array_merge($row[$list] ?? [], $items);
                }
            }

            return Record::from($row);
        })->values();
    }

    protected function row(string $name, ?string $id, ?string $overlay = null): ?Record
    {
        return $id ? $this->rows($name, $overlay)->firstWhere('id', $id) : null;
    }
}
