<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarbonFootprintCachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carbon_footprint_caches', function (Blueprint $table) {
            $table->id();
            $table->float('activity', 8, 2);
            $table->char('activity_type', 10);
            $table->char('fuel_type', 30)->nullable();
            $table->char('mode', 30)->nullable();
            $table->char('country', 10);
            $table->float('carbon_footprint', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carbon_footprint_caches');
    }
}
