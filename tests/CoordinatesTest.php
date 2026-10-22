<?php

namespace Laraversine\Test;

use InvalidArgumentException;
use Laraversine\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CoordinatesTest extends TestCase
{
    #[Test]
    public function builds_from_a_latitude_longitude_array(): void
    {
        $coordinates = Coordinates::from([7.127512, 125.637049]);

        $this->assertSame(7.127512, $coordinates->latitude);
        $this->assertSame(125.637049, $coordinates->longitude);
        $this->assertSame([7.127512, 125.637049], $coordinates->toArray());
    }

    #[Test]
    public function accepts_numeric_strings_and_ignores_keys(): void
    {
        $coordinates = Coordinates::from(['a' => '7.5', 'b' => '125.5']);

        $this->assertSame([7.5, 125.5], $coordinates->toArray());
    }

    #[Test]
    public function accepts_the_extreme_values(): void
    {
        $this->assertSame([-90.0, 180.0], Coordinates::from([-90, 180])->toArray());
    }

    #[Test]
    #[DataProvider('invalid')]
    public function rejects_invalid_input(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Coordinates::from($input);
    }

    public static function invalid(): iterable
    {
        yield 'one element' => [[7.1]];
        yield 'three elements' => [[1, 2, 3]];
        yield 'non numeric' => [['abc', 1]];
        yield 'latitude too high' => [[90.0001, 0]];
        yield 'latitude too low' => [[-91, 0]];
        yield 'longitude too high' => [[0, 180.5]];
        yield 'longitude too low' => [[0, -181]];
        yield 'nan' => [[NAN, 0]];
        yield 'infinite' => [[0, INF]];
    }
}
