<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();

            $table->text('name')->charset('utf8');
            $table->text('description')->nullable()->charset('utf8');
            $table->text('image')->nullable()->charset('utf8');
            $table->text('line')->nullable()->charset('utf8');
            $table->string('phone', 100)->charset('utf8')->nullable();

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
        Schema::dropIfExists('services');
    }
}
