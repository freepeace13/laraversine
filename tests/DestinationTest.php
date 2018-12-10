<?php

namespace Laraversine\Test;

use Laraversine\Test\TestCase;
use Laraversine\Destination;

class DestinationTest extends TestCase
{
  /**
   * Setup the test environment
   */
  protected function setUp()
  {
    parent::setUp();
    $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
    $this->insertDummyData();
  }

  /**
   * Test if the table has data
   *
   * @return void
   */
  public function test_table_has_data()
  {
    $destinations = Destination::all();
    $this->assertTrue($destinations->count() > 0);
  }

  /**
   * Test nearby destination within specific radius
   *
   * @return void
   */
  public function test_nearby()
  {
    $destination = Destination::nearby([
        7.127512,
        125.637049
      ])
      ->within(0.1)
      ->get();

    $this->assertTrue($destination->count() === 1);
    $this->assertTrue($destination->first()->name === 'Dest 6');
  }
}
