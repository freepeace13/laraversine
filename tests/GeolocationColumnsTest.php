<?php

namespace Laraversine\Test;

use Laraversine\Destination;
use Laraversine\Test\Support\Store;
use PHPUnit\Framework\Attributes\Test;

class GeolocationColumnsTest extends TestCase
{
    #[Test]
    public function destination_uses_the_default_columns(): void
    {
        $model = new Destination;

        $this->assertSame('latitude', $model->getLatitudeColumn());
        $this->assertSame('longitude', $model->getLongitudeColumn());
        $this->assertSame('destinations.latitude', $model->getQualifiedLatitudeColumn());
        $this->assertSame('destinations.longitude', $model->getQualifiedLongitudeColumn());
        $this->assertNull($model->getGeolocationDriverName());
    }

    #[Test]
    public function columns_can_be_set_with_properties(): void
    {
        $model = new Store;

        $this->assertSame('lat', $model->getLatitudeColumn());
        $this->assertSame('lng', $model->getLongitudeColumn());
        $this->assertSame('stores.lat', $model->getQualifiedLatitudeColumn());
        $this->assertSame('stores.lng', $model->getQualifiedLongitudeColumn());
    }

    #[Test]
    public function columns_can_be_set_by_overriding_the_getters(): void
    {
        $model = new class extends Destination
        {
            protected $table = 'destinations';

            public function getLatitudeColumn(): string
            {
                return 'y';
            }

            public function getLongitudeColumn(): string
            {
                return 'x';
            }
        };

        $this->assertSame('destinations.y', $model->getQualifiedLatitudeColumn());
        $this->assertSame('destinations.x', $model->getQualifiedLongitudeColumn());
    }

    #[Test]
    public function the_driver_can_be_chosen_per_model(): void
    {
        $model = new class extends Destination
        {
            protected string $geolocationDriver = 'custom';
        };

        $this->assertSame('custom', $model->getGeolocationDriverName());
    }
}
