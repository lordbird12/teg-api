<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeeMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_masters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->charset('utf8')->nullable();

            $table->integer('category_fee_master_id')->unsigned()->index();
            $table->foreign('category_fee_master_id')->references('id')->on('category_fee_masters')->onDelete('cascade');

            $table->text('name')->nullable()->charset('utf8');
            $table->integer('price')->default(0);

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
        Schema::dropIfExists('fee_masters');
    }
}
