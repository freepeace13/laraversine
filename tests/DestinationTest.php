<?php

namespace Laraversine\Test;

use Laraversine\Test\TestCase;
use Laraversine\Destination;

class DestinationTest extends TestCase
{
  /**
   * Setup the test environment
   */
  protected function setUp(): void
  {
    parent::setUp();

    $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
  }
}
