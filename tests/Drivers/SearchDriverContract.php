<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Destination;
use Laraversine\Test\Support\BruteForce;
use Laraversine\Test\Support\DatasetFactory;
use Laraversine\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every search driver must produce exactly what a brute-force scan produces.
 * A driver is covered by adding a subclass that names it.
 */
abstract class SearchDriverContract extends TestCase
{
    abstract protected function driverName(): string;

    /** Insert the dataset. A driver that reads a stored column fills it here. */
    protected function seedDataset(array $rows): void
    {
        DatasetFactory::seed($rows);
    }

    /** Register the driver under test with the manager, if it is not built in. */
    protected function registerDrivers(): void {}

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laraversine.driver', $this->driverName());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerDrivers();
        Destination::query()->delete();
    }

    /** @return iterable<string, array{0: list<array>, 1: list<array{0: float, 1: float}>}> */
    public static function scenarios(): iterable
    {
        yield 'uniform world' => [DatasetFactory::uniform(400, 1), [
            [0, 0], [51.5, -0.12], [-33.9, 151.2], [7.1, 125.6], [-89.9, -40], [0, 179.9], [0, -179.9],
        ]];

        yield 'dense cluster' => [DatasetFactory::cluster(400, 2, 7.1, 125.6, 0.25), [
            [7.1, 125.6], [7.2, 125.5], [7.0, 125.8], [7.1, 125.62],
        ]];

        yield 'antimeridian' => [DatasetFactory::antimeridian(300, 3), [
            [0, 179.95], [0, -179.95], [0.5, 180], [0, 180],
        ]];

        yield 'poles' => [DatasetFactory::poles(300, 4), [
            [90, 0], [-90, 0], [89.95, 10], [-89.8, -120], [89, 100],
        ]];

        yield 'high latitude, wide radius' => [DatasetFactory::cluster(300, 5, 80, 0, 12), [
            [80, 0], [78, 40], [82, -30],
        ]];
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function nearby_matches_brute_force(array $rows, array $origins): void
    {
        $this->seedDataset($rows);
        $stored = DatasetFactory::stored();

        foreach ($origins as $origin) {
            foreach ([1.0, 5.0, 50.0, 500.0, 5000.0] as $radius) {
                $expected = BruteForce::within($stored, $origin, $radius);
                $actual = Destination::nearby($origin, $radius)->get();
                $context = 'origin '.json_encode($origin)." radius {$radius}";

                $this->assertEqualsCanonicalizing(array_keys($expected), $actual->pluck('id')->all(), $context);

                foreach ($actual as $model) {
                    $this->assertEqualsWithDelta($expected[$model->id], (float) $model->distance, 0.000001, $context);
                }
            }
        }
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function nearest_matches_brute_force(array $rows, array $origins): void
    {
        $this->seedDataset($rows);
        $stored = DatasetFactory::stored();

        foreach ($origins as $origin) {
            foreach ([1, 5, 50] as $take) {
                $expected = BruteForce::nearestIds($stored, $origin, $take);
                $result = Destination::nearest($origin, take: $take);
                $actual = $take === 1 ? array_filter([$result?->id]) : $result->pluck('id')->all();

                $this->assertSame($expected, array_values($actual), 'origin '.json_encode($origin)." take {$take}");
            }
        }
    }

    #[Test]
    public function nearest_finds_a_single_far_away_row(): void
    {
        $this->seedDataset([['name' => 'lonely', 'latitude' => -45.0, 'longitude' => 170.0]]);

        $this->assertSame('lonely', Destination::nearest([60.0, -20.0])->name);
    }
}
