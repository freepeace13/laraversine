<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Coordinates;
use Laraversine\Destination;
use Laraversine\Drivers\SqlDriver;
use Laraversine\Haversine;
use Laraversine\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SqlDriverTest extends TestCase
{
    private const ORIGIN = [7.127512, 125.637049];

    private function apply($query, array $origin, float $radius)
    {
        return (new SqlDriver)->applyRadius($query, Coordinates::from($origin), $radius, 'km');
    }

    #[Test]
    public function returns_rows_inside_the_radius_with_a_distance(): void
    {
        $rows = $this->apply(Destination::query(), self::ORIGIN, 0.1)->get();

        $this->assertSame(['Dest 6'], $rows->pluck('name')->all());
        $this->assertNotNull($rows->first()->getAttributes()['distance']);
    }

    #[Test]
    public function the_exact_check_excludes_box_corners(): void
    {
        // Inside the 100 km bounding box of (0,0) but about 126 km away.
        Destination::insert([
            ['name' => 'corner', 'latitude' => 0.8, 'longitude' => 0.8],
            ['name' => 'inside', 'latitude' => 0.5, 'longitude' => 0.5],
        ]);

        $rows = $this->apply(Destination::query(), [0, 0], 100);

        $this->assertSame(['inside'], $rows->get()->pluck('name')->all());
    }

    #[Test]
    public function a_small_radius_adds_a_bounding_box(): void
    {
        $sql = strtolower($this->apply(Destination::query(), self::ORIGIN, 5)->toSql());

        $this->assertSame(2, substr_count($sql, ' between '));
    }

    #[Test]
    public function a_huge_radius_skips_the_bounding_box(): void
    {
        $sql = strtolower($this->apply(Destination::query(), self::ORIGIN, 20100)->toSql());

        $this->assertStringNotContainsString(' between ', $sql);
    }

    #[Test]
    public function a_radius_of_half_the_circumference_includes_the_antipode(): void
    {
        Destination::insert([['name' => 'Antipode', 'latitude' => -7.127341, 'longitude' => -54.37073]]);

        $rows = $this->apply(Destination::query(), [7.127341, 125.629270], Haversine::halfCircumference('km'))->get();

        $this->assertContains('Antipode', $rows->pluck('name')->all());
    }

    #[Test]
    public function crossing_the_antimeridian_finds_both_sides(): void
    {
        Destination::insert([
            ['name' => 'east', 'latitude' => 0, 'longitude' => 179.99],
            ['name' => 'west', 'latitude' => 0, 'longitude' => -179.99],
        ]);

        $query = $this->apply(Destination::query(), [0, 179.995], 5);

        $this->assertStringContainsString(' or ', strtolower($query->toSql()));
        $this->assertEqualsCanonicalizing(['east', 'west'], $query->get()->pluck('name')->all());
    }

    #[Test]
    public function an_origin_on_a_pole_finds_points_at_any_longitude(): void
    {
        Destination::insert([
            ['name' => 'a', 'latitude' => 89.99, 'longitude' => 10],
            ['name' => 'b', 'latitude' => 89.99, 'longitude' => -170],
        ]);

        $rows = $this->apply(Destination::query(), [90, 0], 10)->get();

        $this->assertEqualsCanonicalizing(['a', 'b'], $rows->pluck('name')->all());
    }

    #[Test]
    public function keeps_a_custom_select_and_appends_the_distance(): void
    {
        $row = $this->apply(Destination::query()->select('id'), self::ORIGIN, 50)->first();

        $keys = array_keys($row->getAttributes());
        sort($keys);

        $this->assertSame(['distance', 'id'], $keys);
    }
}
