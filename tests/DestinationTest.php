<?php

namespace Laraversine\Test;

use InvalidArgumentException;
use Laraversine\Destination;
use Laraversine\Distance;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

class DestinationTest extends TestCase
{
    private const ORIGIN = [7.127512, 125.637049];

    #[Test]
    public function table_has_data(): void
    {
        $this->assertSame(6, Destination::count());
    }

    #[Test]
    public function nearby_within_radius(): void
    {
        $results = Destination::nearby(self::ORIGIN)->within(0.1)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Dest 6', $results->first()->name);
    }

    #[Test]
    public function nearby_accepts_radius_argument(): void
    {
        $this->assertCount(1, Destination::nearby(self::ORIGIN, 0.1)->get());
    }

    #[Test]
    public function results_can_be_sorted_nearest_first(): void
    {
        $results = Destination::nearby(self::ORIGIN)->nearestFirst()->get();

        $this->assertSame('Dest 6', $results->first()->name);
        $this->assertSame('Dest 5', $results->get(1)->name);
        $this->assertGreaterThan($results->first()->distance, $results->last()->distance);
    }

    #[Test]
    public function exact_match_has_zero_distance(): void
    {
        $dest = Destination::first();
        $result = Destination::nearby([$dest->latitude, $dest->longitude])->whereKey($dest->id)->first();

        $this->assertEqualsWithDelta(0.0, (float) $result->distance, 0.001);
    }

    #[Test]
    public function miles_are_smaller_than_km(): void
    {
        $km = Destination::nearby(self::ORIGIN)->where('name', 'Dest 1')->first()->distance;

        config(['laraversine.search_by' => 'mile']);
        $mi = Destination::nearby(self::ORIGIN)->where('name', 'Dest 1')->first()->distance;

        $this->assertEqualsWithDelta($km * 0.621371, $mi, 0.01);
    }

    #[Test]
    public function distance_accessor_carries_value_and_unit(): void
    {
        $model = Destination::nearby(self::ORIGIN)->where('name', 'Dest 1')->first();

        $distance = $model->distance();

        $this->assertInstanceOf(Distance::class, $distance);
        $this->assertSame(Distance::KM, $distance->unit);
        $this->assertEqualsWithDelta((float) $model->distance, $distance->value, 0.0000001);
        $this->assertEquals(['value' => $distance->value, 'unit' => 'km'], $distance->jsonSerialize());
    }

    #[Test]
    public function distance_accessor_follows_configured_unit(): void
    {
        config(['laraversine.search_by' => 'mile']);

        $distance = Destination::nearby(self::ORIGIN)->where('name', 'Dest 1')->first()->distance();

        $this->assertSame(Distance::MILE, $distance->unit);
        $this->assertEqualsWithDelta($distance->value * 1.609344, $distance->toKm(), 0.0000001);
    }

    #[Test]
    public function distance_accessor_is_null_without_nearby(): void
    {
        $model = Destination::first();

        $this->assertNull($model->distance());
        $this->assertNull($model->distance);
    }

    #[Test]
    public function distance_attribute_stays_numeric(): void
    {
        $model = Destination::nearby(self::ORIGIN)->first();

        $this->assertIsNumeric($model->distance);
    }

    #[Test]
    public function distance_converts_between_units(): void
    {
        $distance = new Distance(10.0, Distance::KM);

        $this->assertEqualsWithDelta(6.21371, $distance->toMiles(), 0.0001);
        $this->assertSame(10.0, $distance->toKm());
        $this->assertSame('10 km', (string) $distance);
        $this->assertSame('6.21 mi', (string) new Distance($distance->toMiles(), Distance::MILE));
    }

    #[Test]
    public function distance_rejects_unknown_unit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Distance(1.0, 'furlong');
    }

    #[Test]
    public function within_requires_nearby(): void
    {
        $this->expectException(LogicException::class);

        Destination::query()->within(1);
    }

    #[Test]
    public function nearest_first_requires_nearby(): void
    {
        $this->expectException(LogicException::class);

        Destination::query()->nearestFirst();
    }

    #[Test]
    public function rejects_malformed_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearby([7.127512]);
    }

    #[Test]
    public function rejects_out_of_range_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Destination::nearby([95.0, 125.0]);
    }
}
