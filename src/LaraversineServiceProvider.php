<?php

namespace Laraversine;

use Illuminate\Support\ServiceProvider;

class LaraversineServiceProvider extends ServiceProvider
{
  /**
  * Publishes configuration file.
  *
  * @return void
  */
  public function boot()
  {
    $this->publishes([
      __DIR__.'/../config/laraversine.php' => config_path('laraversine.php')
    ], 'laraversine');
  }

  /**
  * Make config publishment optional by merging the config from the package.
  *
  * @return void
  */
  public function register()
  {
    $this->mergeConfigFrom(
      __DIR__.'/../config/laraversine.php',
      'laraversine'
    );
  }
}
