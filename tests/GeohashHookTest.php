<?php

namespace Laraversine\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Destination;
use Laraversine\Geohash;
use Laraversine\Test\Support\GeohashStore;
use Laraversine\Test\Support\Store;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

final class GeohashHookTest extends TestCase
{
    #[Test]
    public function it_sets_the_hash_on_create_and_update(): void
    {
        config(['laraversine.driver' => 'geohash']);

        $destination = Destination::create(['name' => 'a', 'latitude' => 7.1, 'longitude' => 125.6]);

        $this->assertSame(Geohash::encode(7.1, 125.6), $destination->geohash);

        $destination->update(['latitude' => 10.0]);

        $this->assertSame(Geohash::encode(10.0, 125.6), $destination->fresh()->geohash);
    }

    #[Test]
    public function it_leaves_the_hash_alone_when_coordinates_are_unchanged(): void
    {
        config(['laraversine.driver' => 'geohash']);

        $destination = Destination::create(['name' => 'a', 'latitude' => 7.1, 'longitude' => 125.6]);
        DB::table('destinations')->where('id', $destination->id)->update(['geohash' => 'sentinel0000']);

        $destination = $destination->fresh();
        $destination->name = 'renamed';
        $destination->save();

        $this->assertSame('sentinel0000', $destination->fresh()->geohash);
    }

    #[Test]
    public function it_fills_an_empty_hash_even_when_coordinates_are_unchanged(): void
    {
        config(['laraversine.driver' => 'geohash']);

        $id = DB::table('destinations')->insertGetId(['name' => 'a', 'latitude' => 7.1, 'longitude' => 125.6]);

        $destination = Destination::find($id);
        $destination->name = 'renamed';
        $destination->save();

        $this->assertSame(Geohash::encode(7.1, 125.6), $destination->fresh()->geohash);
    }

    #[Test]
    public function null_or_invalid_coordinates_set_a_null_hash(): void
    {
        $nowhere = GeohashStore::create(['name' => 'nowhere', 'lat' => null, 'lng' => null]);
        $invalid = GeohashStore::create(['name' => 'invalid', 'lat' => 95.0, 'lng' => 10.0]);

        $this->assertNull($nowhere->fresh()->hash);
        $this->assertNull($invalid->fresh()->hash);
    }

    #[Test]
    public function it_is_inert_for_models_without_the_geohash_column(): void
    {
        $store = Store::create(['name' => 'plain', 'lat' => 7.1, 'lng' => 125.6]);
        $store->update(['lat' => 7.2]);

        $this->assertArrayNotHasKey('geohash', $store->getAttributes());
        $this->assertNull($store->fresh()->hash);

        $region = new class extends Model
        {
            use HasGeolocation;

            public $timestamps = false;

            protected $table = 'regions';

            protected $guarded = [];
        };

        $created = $region->newQuery()->create(['latitude' => 7.1, 'longitude' => 125.6]);
        $created->update(['latitude' => 7.2]);

        $this->assertArrayNotHasKey('geohash', $created->getAttributes());
    }

    #[Test]
    public function it_does_nothing_when_the_default_driver_is_sql(): void
    {
        $destination = Destination::create(['name' => 'a', 'latitude' => 7.1, 'longitude' => 125.6]);

        $this->assertNull($destination->fresh()->geohash);
    }

    #[Test]
    public function it_honours_a_custom_column_and_per_model_driver(): void
    {
        $near = GeohashStore::create(['name' => 'near', 'lat' => 7.1276, 'lng' => 125.6371]);
        GeohashStore::create(['name' => 'far', 'lat' => 9.0, 'lng' => 126.5]);

        $this->assertSame(Geohash::encode(7.1276, 125.6371), $near->fresh()->hash);

        $names = GeohashStore::nearby([7.127, 125.637], 1)->pluck('name')->all();

        $this->assertSame(['near'], $names);
    }

    #[Test]
    public function a_model_pinned_to_geohash_without_the_column_throws_on_save(): void
    {
        $region = new class extends Model
        {
            use HasGeolocation;

            public $timestamps = false;

            protected $table = 'regions';

            protected $guarded = [];

            protected $geolocationDriver = 'geohash';
        };

        $this->expectException(LogicException::class);

        $region->newQuery()->create(['latitude' => 7.1, 'longitude' => 125.6]);
    }
}
