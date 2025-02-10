<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryOrderAddOnServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_order_add_on_services', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('delivery_order_id')->unsigned()->index();
            $table->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('cascade');

            $table->integer('delivery_order_tk_id')->unsigned()->index();
            $table->foreign('delivery_order_tk_id')->references('id')->on('delivery_order_trackings')->onDelete('cascade');

            $table->integer('add_on_service_id')->unsigned()->index();
            $table->foreign('add_on_service_id')->references('id')->on('add_on_services')->onDelete('cascade');

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
        Schema::dropIfExists('delivery_order_add_on_services');
    }
}
