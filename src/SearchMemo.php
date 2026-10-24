<?php

namespace Laraversine;

use Closure;

/**
 * Remembers facts a driver works out while one logical search runs several
 * queries (nearest() widens its radius round by round), so each is computed
 * once. Outside a scope nothing is remembered.
 *
 * @internal
 */
final class SearchMemo
{
    /** @var array<string, mixed>|null */
    private static ?array $facts = null;

    public static function scope(Closure $search): mixed
    {
        $outer = self::$facts;
        self::$facts ??= [];

        try {
            return $search();
        } finally {
            self::$facts = $outer;
        }
    }

    public static function remember(string $key, Closure $compute): mixed
    {
        if (self::$facts === null) {
            return $compute();
        }

        if (! array_key_exists($key, self::$facts)) {
            self::$facts[$key] = $compute();
        }

        return self::$facts[$key];
    }
}
