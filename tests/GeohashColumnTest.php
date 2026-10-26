<?php

namespace Laraversine\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Destination;
use Laraversine\GeohashColumn;
use PHPUnit\Framework\Attributes\Test;

final class GeohashColumnTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('late_places');
        GeohashColumn::forget();

        parent::tearDown();
    }

    #[Test]
    public function a_missing_column_is_checked_again_after_a_minute(): void
    {
        GeohashColumn::forget();
        Schema::create('late_places', function ($table) {
            $table->id();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });

        $model = new class extends Model
        {
            use HasGeolocation;

            protected $table = 'late_places';
        };

        $this->assertFalse(GeohashColumn::exists($model));

        // A migration adds the column while this process keeps running.
        Schema::table('late_places', fn ($table) => $table->string('geohash', 12)->nullable());

        $this->assertFalse(GeohashColumn::exists($model), 'remembered for a short while');

        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        $this->assertTrue(GeohashColumn::exists($model));
    }

    #[Test]
    public function an_existing_column_is_remembered_without_asking_the_schema_again(): void
    {
        GeohashColumn::forget();
        $model = new Destination;

        $this->assertTrue(GeohashColumn::exists($model));

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        Carbon::setTestNow(Carbon::now()->addDay());

        $this->assertTrue(GeohashColumn::exists($model));
        $this->assertSame(0, $queries);
    }
}
