<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();

            $table->enum('vehicle', ['Car', 'Ship'])->charset('utf8')->default('Car');

            $table->text('type')->nullable()->charset('utf8');
            $table->text('name')->charset('utf8');
            $table->string('kg', 100)->charset('utf8')->nullable();
            $table->string('cbm', 100)->charset('utf8')->nullable();

            $table->enum('status', ['Yes', 'No','Request'])->charset('utf8')->default('Request');

            $table->string('create_by', 100)->charset('utf8')->nullable();
            $table->string('update_by', 100)->charset('utf8')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('price_rates');
    }
}
