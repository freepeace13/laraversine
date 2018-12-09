<?php

namespace Laraversine\Test;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
  /**
   * Load the laraversine service provider.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return void
   */
  protected function getPackageProviders($app)
  {
    return ['Laraversine\LaraversineServiceProvider'];
  }
}
