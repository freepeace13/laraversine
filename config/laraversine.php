<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search measurement unit
    |--------------------------------------------------------------------------
    |
    | The unit used for distances and radii in "nearby" and "nearest".
    |
    | Supported: "km", "mile"
    |
    */
    'search_by' => 'km',

    /*
    |--------------------------------------------------------------------------
    | Search driver
    |--------------------------------------------------------------------------
    |
    | How a radius search is executed. A model can override this with a
    | protected string $geolocationDriver property. Add your own with
    | Laraversine::extend('name', fn () => new YourDriver).
    |
    | Supported: "sql", "geohash"
    |
    */
    'driver' => 'sql',

    /*
    |--------------------------------------------------------------------------
    | nearest()
    |--------------------------------------------------------------------------
    |
    | nearest() searches in rounds, doubling the radius until it has enough
    | rows. Both values are in the unit configured by "search_by".
    |
    | initial_radius: the radius of the first round.
    | max_radius:     stop widening here. null means search the whole globe.
    |
    */
    'nearest' => [
        'initial_radius' => 1,
        'max_radius' => null,
    ],
];
