<?php

namespace Laraversine\Test;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laraversine\LaraversineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\Attributes\Test;

class ProviderTest extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaraversineServiceProvider::class];
    }

    #[Test]
    public function migrating_creates_no_destinations_table(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $this->assertFalse(Schema::hasTable('destinations'));
    }

    #[Test]
    public function the_migration_can_still_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(LaraversineServiceProvider::class, 'laraversine-migrations');

        $this->assertNotEmpty($paths);
        $this->assertStringContainsString('create_destinations_table', implode(',', $this->filesIn(array_key_first($paths))));
    }

    #[Test]
    public function the_config_can_still_be_published(): void
    {
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(LaraversineServiceProvider::class, 'laraversine-config'));
    }

    /** @return list<string> */
    private function filesIn(string $directory): array
    {
        return array_map('basename', glob($directory.'/*.php'));
    }
}
