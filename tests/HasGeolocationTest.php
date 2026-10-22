<?php

namespace Laraversine\Test;

use ArgumentCountError;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laraversine\Destination;
use Laraversine\Distance;
use Laraversine\Drivers\SqlDriver;
use Laraversine\Facades\Laraversine;
use Laraversine\Test\Support\CountingDriver;
use Laraversine\Test\Support\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class HasGeolocationTest extends TestCase
{
    private const ORIGIN = [7.127512, 125.637049];

    #[Test]
    public function nearby_requires_a_radius(): void
    {
        $this->expectException(ArgumentCountError::class);

        Destination::nearby(self::ORIGIN);
    }

    #[Test]
    public function nearby_accepts_the_radius_as_a_named_argument(): void
    {
        $this->assertCount(1, Destination::nearby(self::ORIGIN, radius: 0.1)->get());
    }

    #[Test]
    #[DataProvider('nonPositiveRadii')]
    public function nearby_rejects_a_radius_that_is_not_positive(float $radius): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearby(self::ORIGIN, $radius);
    }

    public static function nonPositiveRadii(): iterable
    {
        yield 'zero' => [0.0];
        yield 'negative' => [-1.0];
    }

    #[Test]
    public function nearby_paginates_with_correct_totals(): void
    {
        $page = Destination::nearby(self::ORIGIN, 50)->nearestFirst()->paginate(2);

        $this->assertSame(6, $page->total());
        $this->assertCount(2, $page->items());
        $this->assertSame('Dest 6', $page->items()[0]->name);
    }

    #[Test]
    public function distance_reports_the_configured_unit(): void
    {
        $distance = Destination::nearby(self::ORIGIN, 50)->first()->distance();

        $this->assertInstanceOf(Distance::class, $distance);
        $this->assertSame(Distance::KM, $distance->unit);
    }

    #[Test]
    public function a_model_extending_authenticatable_supports_the_full_api(): void
    {
        Store::insert([
            ['name' => 'far', 'lat' => 7.2, 'lng' => 125.7],
            ['name' => 'near', 'lat' => 7.1276, 'lng' => 125.6371],
        ]);

        $stores = Store::nearby(self::ORIGIN, 50)->nearestFirst()->get();

        $this->assertSame(['near', 'far'], $stores->pluck('name')->all());
        $this->assertInstanceOf(Distance::class, $stores->first()->distance());
        $this->assertSame('near', Store::where('is_open', true)->nearby(self::ORIGIN, 50)->nearestFirst()->first()->name);
    }

    #[Test]
    public function joining_a_table_with_the_same_column_names_is_not_ambiguous(): void
    {
        // Auto-increment ids are not reset between tests on MySQL, so look the id up.
        $id = Destination::where('name', 'Dest 1')->value('id');
        DB::table('regions')->insert(['id' => $id, 'latitude' => 1, 'longitude' => 1]);

        $rows = Destination::nearby(self::ORIGIN, 50)
            ->join('regions', 'regions.id', '=', 'destinations.id')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Dest 1', $rows->first()->name);
    }

    #[Test]
    public function rows_with_null_coordinates_are_skipped(): void
    {
        Store::insert([
            ['name' => 'no coordinates', 'lat' => null, 'lng' => null],
            ['name' => 'no longitude', 'lat' => 7.1276, 'lng' => null],
            ['name' => 'ok', 'lat' => 7.1276, 'lng' => 125.6371],
        ]);

        $this->assertSame(['ok'], Store::nearby(self::ORIGIN, 50)->get()->pluck('name')->all());
        $this->assertSame(['ok'], Store::nearby(self::ORIGIN, 25000)->get()->pluck('name')->all());
    }

    #[Test]
    public function a_model_can_choose_its_own_driver(): void
    {
        Laraversine::extend('counting', fn () => new CountingDriver(new SqlDriver));
        CountingDriver::$calls = 0;

        $model = new class extends Destination
        {
            protected $table = 'destinations';

            protected string $geolocationDriver = 'counting';
        };

        $model->newQuery()->nearby(self::ORIGIN, 50)->get();
        Destination::nearby(self::ORIGIN, 50)->get();

        $this->assertSame(1, CountingDriver::$calls);
    }
}
