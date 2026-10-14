# Laraversine

Find the nearest destinations in Laravel using the haversine formula.

Requires PHP 8.2+ and Laravel 11 or 12.

## Installation

```bash
composer require freepeace13/laraversine
php artisan migrate
```

The service provider is auto-discovered and loads its own `destinations` migration.
To customise, publish them:

```bash
php artisan vendor:publish --tag=laraversine-config
php artisan vendor:publish --tag=laraversine-migrations
```

## Usage

Coordinates are `[latitude, longitude]`. Each result gets a `distance` attribute.

```php
use Laraversine\Destination;

// All destinations with a distance attribute
Destination::nearby([7.127512, 125.637049])->get();

// Within a radius, nearest first
Destination::nearby([7.127512, 125.637049])->within(2)->nearestFirst()->get();

// Same, radius as an argument
Destination::nearby([7.127512, 125.637049], 2)->get();
```

`within()` and `nearestFirst()` must come after `nearby()`. Works on MySQL/MariaDB,
PostgreSQL and SQLite (SQLite needs `radians`, `cos`, `sin`, `acos` registered).

## Configuration

`config/laraversine.php` → `search_by`: `km` (default) or `mile`. It controls both the
`distance` unit and the radius unit.

## Testing

```bash
composer install
vendor/bin/phpunit
```
