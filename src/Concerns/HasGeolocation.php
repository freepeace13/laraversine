<?php

namespace Laraversine\Concerns;

trait HasGeolocation
{
    public function getLatitudeColumn(): string
    {
        return property_exists($this, 'latitudeColumn') ? $this->latitudeColumn : 'latitude';
    }

    public function getLongitudeColumn(): string
    {
        return property_exists($this, 'longitudeColumn') ? $this->longitudeColumn : 'longitude';
    }

    public function getQualifiedLatitudeColumn(): string
    {
        return $this->qualifyColumn($this->getLatitudeColumn());
    }

    public function getQualifiedLongitudeColumn(): string
    {
        return $this->qualifyColumn($this->getLongitudeColumn());
    }

    public function getGeolocationDriverName(): ?string
    {
        return property_exists($this, 'geolocationDriver') ? $this->geolocationDriver : null;
    }
}
