<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
  protected $fillable = ['name', 'latitude', 'longitude'];

  /**
   * Scope a query to only include nearest destination
   *
   * @param  [type] $query       [description]
   * @param  array  $coordinates [description]
   * @return [type]              [description]
   */
  public function scopeNearby($query, array $coordinates)
  {
    return $query->selectRaw('
      *, (6371 * acos(
        cos(radians(?))
        * cos(radians(latitude))
        * cos(radians(longitude) - radians(?))
        + sin(radians(?))
        * sin(radians(latitude))
      )) AS distance',
      [$coordinates[0], $coordinates[1], $coordinates[0]]
    );
  }

  /**
   * Scope a query to only include nearest destination within the given radius
   * 
   * @param  [type] $query  [description]
   * @param  float  $radius [description]
   * @return [type]         [description]
   */
  public function scopeWithin($query, float $radius)
  {
    return $query->havingRaw('distance < ?', [$radius]);
  }
}
