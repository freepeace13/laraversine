<?php

namespace Laraversine\Test;

use Illuminate\Support\Facades\DB;
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

    #[Test]
    public function verify_accepts_a_hash_one_cell_over_because_of_database_rounding(): void
    {
        Destination::query()->delete();

        [, $maxLat] = Geohash::bounds(Geohash::encode(7.123456, 125.654321));
        $lat = floor($maxLat * 1e8) / 1e8; // inside the cell, within 1e-8 of its north edge
        $lng = 125.654321;
        $neighbour = Geohash::encode($lat + 2e-8, $lng);

        $this->assertNotSame($neighbour, Geohash::encode($lat, $lng));

        Destination::insert([['name' => 'edge', 'latitude' => $lat, 'longitude' => $lng, 'geohash' => $neighbour]]);

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])->assertExitCode(0);
    }

    #[Test]
    public function verify_rejects_a_truncated_hash(): void
    {
        Destination::query()->delete();

        Destination::insert([['name' => 'short', 'latitude' => 7.1, 'longitude' => 125.6, 'geohash' => substr(Geohash::encode(7.1, 125.6), 0, 8)]]);

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])->assertExitCode(1);
    }

    #[Test]
    public function it_repairs_a_chunk_with_a_constant_number_of_updates(): void
    {
        $updates = 0;
        DB::listen(function ($query) use (&$updates) {
            $updates += str_starts_with(strtolower($query->sql), 'update') ? 1 : 0;
        });

        $this->artisan('laraversine:geohash', ['model' => Destination::class])->assertExitCode(0);

        $this->assertLessThanOrEqual(2, $updates);
        $this->assertSame(0, Destination::whereNull('geohash')->count());
    }

    #[Test]
    public function verify_rejects_a_hash_with_a_character_outside_the_alphabet(): void
    {
        Destination::query()->delete();

        // Right length and next to the right cell, but "a" is not a geohash character.
        $broken = substr(Geohash::encode(7.1, 125.6), 0, 11).'a';

        Destination::insert([['name' => 'broken', 'latitude' => 7.1, 'longitude' => 125.6, 'geohash' => $broken]]);

        $this->artisan('laraversine:geohash', ['model' => Destination::class, '--verify' => true])->assertExitCode(1);

        $this->artisan('laraversine:geohash', ['model' => Destination::class])->assertExitCode(0);
        $this->assertSame(Geohash::encode(7.1, 125.6), Destination::first()->geohash);
    }
}
