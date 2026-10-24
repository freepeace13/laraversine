<?php

namespace Laraversine\Test;

use Laraversine\Coordinates;
use Laraversine\Destination;
use Laraversine\Geohash;
use Laraversine\Test\Support\GeohashStore;
use Laraversine\Test\Support\Store;
use PHPUnit\Framework\Attributes\Test;

final class GeohashCommandTest extends TestCase
{
    #[Test]
    public function it_backfills_missing_hashes(): void
    {
        // The default driver is sql: the command is how a table is prepared before switching.
        $this->assertSame(6, Destination::whereNull('geohash')->count());

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--chunk' => 2])->assertExitCode(0);

        foreach (Destination::all() as $destination) {
            $this->assertSame(Geohash::encode($destination->latitude, $destination->longitude), $destination->geohash);
        }
    }

    #[Test]
    public function verify_reports_stale_hashes_and_writes_nothing(): void
    {
        $this->artisan('laraversine:geohash', ['model' => Destination::class])->assertExitCode(0);

        // Bypasses model events, so every hash is now stale.
        Destination::query()->update(['latitude' => 10.0]);
        $before = Destination::pluck('geohash', 'id')->all();

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])
            ->expectsOutputToContain('6 rows')
            ->assertExitCode(1);

        $this->assertSame($before, Destination::pluck('geohash', 'id')->all());

        $this->artisan('laraversine:geohash', ['model' => Destination::class])->assertExitCode(0);
        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])->assertExitCode(0);
    }

    #[Test]
    public function verify_exits_zero_when_everything_matches(): void
    {
        $this->artisan('laraversine:geohash', ['model' => Destination::class])->assertExitCode(0);

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])->assertExitCode(0);
    }

    #[Test]
    public function null_coordinates_expect_a_null_hash(): void
    {
        GeohashStore::insert([
            ['name' => 'nowhere', 'lat' => null, 'lng' => null, 'hash' => 'garbage00000'],
            ['name' => 'here', 'lat' => 7.1, 'lng' => 125.6, 'hash' => null],
        ]);

        $this->artisan('laraversine:geohash', ['model' => GeohashStore::class, '--verify' => true])->assertExitCode(1);
        $this->artisan('laraversine:geohash', ['model' => GeohashStore::class])->assertExitCode(0);

        $this->assertNull(GeohashStore::where('name', 'nowhere')->value('hash'));
        $this->assertSame(Geohash::encode(7.1, 125.6), GeohashStore::where('name', 'here')->value('hash'));
    }

    #[Test]
    public function it_rejects_a_class_without_the_trait(): void
    {
        $this->artisan('laraversine:geohash', ['model' => Coordinates::class])->assertExitCode(1);
        $this->artisan('laraversine:geohash', ['model' => 'App\\Models\\Missing'])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_model_whose_table_has_no_geohash_column(): void
    {
        // The stores table only has the custom "hash" column, not "geohash".
        $this->artisan('laraversine:geohash', ['model' => Store::class])->assertExitCode(1);
    }
}
