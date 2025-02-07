<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_lists', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('order_id')->unsigned()->index();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->text('product_code')->nullable()->charset('utf8');
            $table->text('product_name')->nullable()->charset('utf8');
            $table->text('product_url')->nullable()->charset('utf8');
            $table->text('product_image')->nullable()->charset('utf8');
            $table->text('product_category')->nullable()->charset('utf8');
            $table->text('product_store_type')->nullable()->charset('utf8');
            $table->text('product_note')->nullable()->charset('utf8');

            $table->integer('cost')->default(0);
            $table->integer('price')->default(0);

            $table->integer('qty')->default(0);

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
        Schema::dropIfExists('order_lists');
    }
}
