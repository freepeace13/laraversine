<?php

namespace Laraversine\Test;

use Illuminate\Support\Facades\DB;
use Laraversine\Coordinates;
use Laraversine\Destination;
use Laraversine\Distance;
use Laraversine\Haversine;
use Laraversine\Test\Support\BruteForce;
use PHPUnit\Framework\Attributes\Test;

class HaversineTest extends TestCase
{
    /** Distance from $origin to the destination row called $name, via the generated SQL. */
    private function sqlDistance(array $origin, string $name, string $unit = 'km'): float
    {
        [$expression, $bindings] = Haversine::sql(
            'latitude',
            'longitude',
            Coordinates::from($origin),
            $unit,
            DB::connection()->getDriverName() === 'sqlite',
        );

        return (float) DB::table('destinations')
            ->where('name', $name)
            ->selectRaw("{$expression} AS distance", $bindings)
            ->value('distance');
    }

    #[Test]
    public function sql_has_five_placeholders_matching_its_bindings(): void
    {
        [$expression, $bindings] = Haversine::sql('lat', 'lng', Coordinates::from([10, 20]), 'km');

        $this->assertSame(5, substr_count($expression, '?'));
        $this->assertSame([10.0, 10.0, 10.0, 20.0, 20.0], $bindings);
    }

    #[Test]
    public function sqlite_uses_min_instead_of_least(): void
    {
        [$sqlite] = Haversine::sql('lat', 'lng', Coordinates::from([0, 0]), 'km', true);
        [$other] = Haversine::sql('lat', 'lng', Coordinates::from([0, 0]), 'km', false);

        $this->assertStringContainsString('min(1.0', $sqlite);
        $this->assertStringContainsString('least(1.0', $other);
    }

    #[Test]
    public function reference_city_pairs_match_published_distances_within_half_a_percent(): void
    {
        Destination::insert([
            ['name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278],
            ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
            ['name' => 'Los Angeles', 'latitude' => 34.0522, 'longitude' => -118.2437],
        ]);

        $this->assertEqualsWithDelta(343.5, $this->sqlDistance([51.5074, -0.1278], 'Paris'), 343.5 * 0.005);
        $this->assertEqualsWithDelta(3936.0, $this->sqlDistance([40.7128, -74.0060], 'Los Angeles'), 3936.0 * 0.005);
    }

    #[Test]
    public function short_distances_are_accurate_to_one_metre(): void
    {
        // 0.00045 degrees of latitude is about 50 m.
        Destination::insert([['name' => 'Near', 'latitude' => 7.127341 + 0.00045, 'longitude' => 125.629270]]);

        $expected = BruteForce::distance(7.127341, 125.629270, 7.127341 + 0.00045, 125.629270);

        $this->assertEqualsWithDelta($expected, $this->sqlDistance([7.127341, 125.629270], 'Near'), 0.001);
    }

    #[Test]
    public function identical_points_are_exactly_zero(): void
    {
        $this->assertSame(0.0, $this->sqlDistance([7.127341, 125.629270], 'Dest 1'));
    }

    #[Test]
    public function matches_the_php_oracle_to_six_decimals(): void
    {
        $origin = [7.127512, 125.637049];

        foreach (Destination::all() as $destination) {
            $expected = BruteForce::distance($origin[0], $origin[1], $destination->latitude, $destination->longitude);

            $this->assertEqualsWithDelta($expected, $this->sqlDistance($origin, $destination->name), 0.000001);
        }
    }

    #[Test]
    public function uses_the_miles_radius_for_miles(): void
    {
        $origin = [7.127512, 125.637049];
        $dest = Destination::where('name', 'Dest 1')->first();

        $expected = BruteForce::distance($origin[0], $origin[1], $dest->latitude, $dest->longitude, Haversine::EARTH_RADIUS_MILES);

        $this->assertEqualsWithDelta($expected, $this->sqlDistance($origin, 'Dest 1', 'mile'), 0.000001);
    }

    #[Test]
    public function reads_the_unit_from_config(): void
    {
        $this->assertSame(Distance::KM, Haversine::unit());

        config(['laraversine.search_by' => 'mile']);

        $this->assertSame(Distance::MILE, Haversine::unit());
        $this->assertSame(3958.8, Haversine::earthRadius('mile'));
        $this->assertEqualsWithDelta(M_PI * 6371.0, Haversine::halfCircumference('km'), 0.0001);
    }

    #[Test]
    public function literal_inlines_a_float_with_eight_decimals(): void
    {
        $this->assertSame('1.50000000', Haversine::literal(1.5));
        $this->assertSame('0.30000000', Haversine::literal(0.1 + 0.2));
        $this->assertSame('-180.00000000', Haversine::literal(-180));
    }
}
