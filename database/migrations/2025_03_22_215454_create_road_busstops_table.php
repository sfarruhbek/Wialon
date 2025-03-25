<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('road_busstops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('road_id')->constrained('roads')->onDelete('cascade');
            $table->foreignId('bus_stop_id')->constrained('bus_stops')->onDelete('cascade');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('road_busstops');
    }
};
