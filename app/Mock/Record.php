<?php

namespace App\Mock;

use Illuminate\Support\Fluent;

/**
 * Lightweight, schema-less record used by the Phase 1 mock repositories.
 *
 * Blade reads `$lead->business_name`, exactly as it will read an Eloquent
 * model in Phase 2, so the views do not need rewriting. Unknown attributes
 * resolve to null instead of throwing.
 */
class Record extends Fluent
{
    public static function from(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn ($v) => static::from($v), $value);
        }

        return new static(array_map(fn ($v) => static::from($v), $value));
    }

    /** Recursively convert back to plain arrays (for JSON embedding). */
    public function plain(): array
    {
        return array_map(function ($v) {
            if ($v instanceof self) {
                return $v->plain();
            }
            if (is_array($v)) {
                return array_map(fn ($i) => $i instanceof self ? $i->plain() : $i, $v);
            }

            return $v;
        }, $this->attributes);
    }
}
