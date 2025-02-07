<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderAddOnServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_add_on_services', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('order_id')->unsigned()->index();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->integer('order_list_id')->unsigned()->index();
            $table->foreign('order_list_id')->references('id')->on('order_lists')->onDelete('cascade');

            $table->integer('add_on_service_id')->unsigned()->index();
            $table->foreign('add_on_service_id')->references('id')->on('add_on_services')->onDelete('cascade');

            $table->integer('add_on_service_price')->default(0);

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
        Schema::dropIfExists('order_add_on_services');
    }
}
