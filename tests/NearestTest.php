<?php

namespace Laraversine\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laraversine\Destination;
use Laraversine\Test\Support\BruteForce;
use Laraversine\Test\Support\Store;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

class NearestTest extends TestCase
{
    private const ORIGIN = [7.127512, 125.637049];

    /** @return list<array{id: int, latitude: float, longitude: float}> */
    private function stored(): array
    {
        return Destination::query()->get(['id', 'latitude', 'longitude'])
            ->map(fn ($d) => ['id' => $d->id, 'latitude' => (float) $d->latitude, 'longitude' => (float) $d->longitude])
            ->all();
    }

    #[Test]
    public function returns_the_single_nearest_model(): void
    {
        $nearest = Destination::nearest(self::ORIGIN);

        $this->assertInstanceOf(Destination::class, $nearest);
        $this->assertSame('Dest 6', $nearest->name);
        $this->assertNotNull($nearest->distance);
    }

    #[Test]
    public function returns_a_collection_for_take_above_one(): void
    {
        $results = Destination::nearest(self::ORIGIN, take: 3);

        $this->assertSame(
            BruteForce::nearestIds($this->stored(), self::ORIGIN, 3),
            $results->pluck('id')->all(),
        );
    }

    #[Test]
    public function respects_existing_query_constraints(): void
    {
        $nearest = Destination::where('name', '!=', 'Dest 6')->nearest(self::ORIGIN);

        $this->assertSame('Dest 5', $nearest->name);
    }

    #[Test]
    public function keeps_a_custom_select_in_every_round(): void
    {
        $nearest = Destination::select('id', 'name')->nearest([14.5995, 120.9842]);

        $this->assertNotNull($nearest->name);
        $this->assertNotNull($nearest->distance);
    }

    #[Test]
    public function ignores_ordering_limit_and_offset_on_the_base_query(): void
    {
        $nearest = Destination::orderBy('name', 'desc')->limit(1)->offset(2)->nearest(self::ORIGIN, take: 2);

        $this->assertSame(
            BruteForce::nearestIds($this->stored(), self::ORIGIN, 2),
            $nearest->pluck('id')->all(),
        );
    }

    #[Test]
    public function stops_widening_at_the_max_radius(): void
    {
        DB::enableQueryLog();

        $this->assertNull(Destination::nearest([0.0, 0.0], maxRadius: 100));

        // Rounds at radius 1, 2, 4, 8, 16, 32, 64 and then the 100 cap.
        $this->assertCount(8, DB::getQueryLog());
    }

    #[Test]
    public function max_radius_can_come_from_config(): void
    {
        config(['laraversine.nearest.max_radius' => 100]);

        $this->assertNull(Destination::nearest([0.0, 0.0]));
    }

    #[Test]
    public function the_number_of_rounds_is_logarithmic_in_the_distance(): void
    {
        $origin = [14.5995, 120.9842]; // Manila, roughly 960 km from the data
        $nearestDistance = array_values(BruteForce::within($this->stored(), $origin, 25000))[0];

        DB::enableQueryLog();
        Destination::nearest($origin);

        $this->assertLessThanOrEqual((int) ceil(log($nearestDistance / 1.0, 2)) + 1, count(DB::getQueryLog()));
    }

    #[Test]
    public function an_uncapped_search_reaches_the_antipode_side_of_the_globe(): void
    {
        $origin = [-7.127512, -54.362951]; // opposite side of the data

        $results = Destination::nearest($origin, take: 6);

        $this->assertSame(
            BruteForce::nearestIds($this->stored(), $origin, 6),
            $results->pluck('id')->all(),
        );
    }

    #[Test]
    public function an_empty_table_returns_null_or_an_empty_collection_and_terminates(): void
    {
        Destination::query()->delete();
        DB::enableQueryLog();

        $this->assertNull(Destination::nearest(self::ORIGIN));
        $this->assertCount(0, Destination::nearest(self::ORIGIN, take: 5));
        $this->assertLessThanOrEqual(2 * 17, count(DB::getQueryLog()));
    }

    #[Test]
    public function asking_for_more_rows_than_exist_returns_them_all(): void
    {
        $this->assertCount(6, Destination::nearest(self::ORIGIN, take: 50));
    }

    #[Test]
    public function rows_with_null_coordinates_are_ignored(): void
    {
        Store::insert([
            ['name' => 'blank', 'lat' => null, 'lng' => null],
            ['name' => 'ok', 'lat' => 7.2, 'lng' => 125.7],
        ]);

        $this->assertSame('ok', Store::nearest(self::ORIGIN)->name);
        $this->assertCount(1, Store::nearest(self::ORIGIN, take: 5));
    }

    #[Test]
    public function the_unit_is_taken_from_config(): void
    {
        config(['laraversine.search_by' => 'mile', 'laraversine.nearest.initial_radius' => 0.5]);

        $this->assertSame('Dest 6', Destination::nearest(self::ORIGIN)->name);
    }

    #[Test]
    public function rejects_a_take_below_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearest(self::ORIGIN, take: 0);
    }

    #[Test]
    public function rejects_a_non_positive_max_radius(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearest(self::ORIGIN, maxRadius: 0);
    }

    #[Test]
    public function rejects_invalid_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearest([95, 0]);
    }

    #[Test]
    public function a_model_without_the_trait_is_rejected(): void
    {
        $this->expectException(LogicException::class);

        (new class extends Model
        {
            protected $table = 'destinations';
        })->newQuery()->nearest(self::ORIGIN);
    }
}
