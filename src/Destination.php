<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Model;
use Laraversine\Concerns\HasGeolocation;

class Destination extends Model
{
    use HasGeolocation;

    protected $fillable = ['name', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
