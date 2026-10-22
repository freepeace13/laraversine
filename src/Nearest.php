<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Finds the nearest rows without a caller-chosen radius: run nearby() with a
 * small radius and double it until it returns $take rows. A round is accepted
 * only when its exact distance filter returns enough rows, so every row it
 * left out is farther than every row it returned and the result is exact.
 *
 * @internal
 */
final class Nearest
{
    public function __construct(
        private Builder $base,
        private Coordinates $origin,
        private int $take,
        private ?float $maxRadius,
    ) {}

    public function get(): Model|Collection|null
    {
        if ($this->take < 1) {
            throw new InvalidArgumentException('take must be at least 1.');
        }

        if ($this->maxRadius !== null && $this->maxRadius <= 0) {
            throw new InvalidArgumentException('maxRadius must be greater than 0.');
        }

        $initial = (float) config('laraversine.nearest.initial_radius', 1);

        if ($initial <= 0) {
            throw new InvalidArgumentException('laraversine.nearest.initial_radius must be greater than 0.');
        }

        $ceiling = $this->ceiling(Haversine::unit());
        $radius = min($initial, $ceiling);

        while (true) {
            // The caller's ordering and paging would change which rows count as
            // the nearest, so each round starts without them.
            $results = (clone $this->base)
                ->reorder()
                ->offset(null)
                ->limit(null)
                ->nearby($this->origin->toArray(), $radius)
                ->nearestFirst()
                ->limit($this->take)
                ->get();

            if ($results->count() >= $this->take || $radius >= $ceiling) {
                break;
            }

            $radius = min($radius * 2, $ceiling);
        }

        return $this->take === 1 ? $results->first() : $results;
    }

    /**
     * The widest radius worth searching: the cap, or the earth's half
     * circumference (which covers everything) when there is none.
     */
    private function ceiling(string $unit): float
    {
        $cap = $this->maxRadius ?? config('laraversine.nearest.max_radius');
        $half = Haversine::halfCircumference($unit);

        return $cap === null ? $half : min((float) $cap, $half);
    }
}
