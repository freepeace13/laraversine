<?php

namespace Laraversine\Test;

use Laraversine\LaraversineServiceProvider;
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
    return [
      LaraversineServiceProvider::class
    ];
  }

  /**
   * Define environment setup.
   *
   * @param  \Illuminate\Foundation\Application  $app
   * @return void
   */
  protected function getEnvironmentSetUp($app)
  {
    $app['config']->set('database.default', 'laraversine');
    $app['config']->set('database.connections.laraversine', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'prefix' => ''
    ]);
  }
}
