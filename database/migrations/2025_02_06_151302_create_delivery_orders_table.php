<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();
            
            $table->integer('order_id')->nullable();

            $table->date('date');
            
            $table->string('track_no')->nullable();

            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();

            $table->text('note')->nullable()->charset('utf8');

            $table->enum('status', ['arrived_china_warehouse','in_transit','arrived_thailand_warehouse','awaiting_payment','delivered'])->charset('utf8')->default('arrived_china_warehouse');
            
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
        Schema::dropIfExists('delivery_orders');
    }
}
