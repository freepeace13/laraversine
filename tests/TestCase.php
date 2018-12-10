<?php

namespace Laraversine\Test;

use Laraversine\LaraversineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Laraversine\Destination;

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
      'driver' => 'mysql',
      'host' => '127.0.0.1',
      'port' => '3306',
      'database' => 'laraversine_test',
      'username' => 'root',
      'password' => 'root'
    ]);
  }

  /**
   * Will insert destination dummy datas
   *
   * @return void
   */
  protected function insertDummyData()
  {
    return Destination::insert([
      ['name' => 'Dest 1', 'latitude' => 7.127341, 'longitude' => 125.629270],
      ['name' => 'Dest 2', 'latitude' => 7.126788, 'longitude' => 125.628540],
      ['name' => 'Dest 3', 'latitude' => 7.127725, 'longitude' => 125.627467],
      ['name' => 'Dest 4', 'latitude' => 7.126873, 'longitude' => 125.629871],
      ['name' => 'Dest 5', 'latitude' => 7.125042, 'longitude' => 125.634033],
      ['name' => 'Dest 6', 'latitude' => 7.128023, 'longitude' => 125.637467]
    ]);
  }
}
