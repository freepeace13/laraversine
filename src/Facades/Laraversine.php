<?php

namespace Laraversine\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Laraversine\Contracts\SearchDriver;
use Laraversine\SearchManager;

/**
 * @method static SearchDriver driver(?string $driver = null)
 * @method static SearchManager extend(string $driver, Closure $callback)
 *
 * @see SearchManager
 */
class Laraversine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchManager::class;
    }
}
