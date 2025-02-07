<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryOrderListImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_order_list_images', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('delivery_order_list_id')->unsigned()->index();
            $table->foreign('delivery_order_list_id')->references('id')->on('delivery_order_lists')->onDelete('cascade');

            $table->string('image_url', 250)->charset('utf8')->nullable();
            $table->string('image', 250)->charset('utf8')->nullable();

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
        Schema::dropIfExists('delivery_order_list_images');
    }
}
