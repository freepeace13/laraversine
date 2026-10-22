<?php

namespace Laraversine\Test\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laraversine\Concerns\HasGeolocation;

class Store extends Authenticatable
{
    use HasGeolocation;

    public $timestamps = false;

    protected $table = 'stores';

    protected $guarded = [];

    protected $casts = ['lat' => 'float', 'lng' => 'float'];

    protected string $latitudeColumn = 'lat';

    protected string $longitudeColumn = 'lng';
}
