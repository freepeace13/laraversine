<?php

namespace Laraversine;

use Illuminate\Support\Manager;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Drivers\SqlDriver;

/**
 * @method SearchDriver driver(?string $driver = null)
 */
class SearchManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('laraversine.driver', 'sql');
    }

    protected function createSqlDriver(): SearchDriver
    {
        return new SqlDriver;
    }
}
