<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same column names as destinations, to prove joins are not ambiguous.
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
