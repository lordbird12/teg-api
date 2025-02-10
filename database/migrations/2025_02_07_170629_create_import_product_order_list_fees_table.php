<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportProductOrderListFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_product_order_list_fees', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('import_product_order_id')->unsigned()->index();
            $table->foreign('import_product_order_id')->references('id')->on('import_product_orders')->onDelete('cascade');

            $table->integer('import_product_or_ls_id')->unsigned()->index();
            $table->foreign('import_product_or_ls_id')->references('id')->on('import_product_order_lists')->onDelete('cascade');

            $table->integer('fee_master_id')->unsigned()->index();
            $table->foreign('fee_master_id')->references('id')->on('fee_masters')->onDelete('cascade');

            $table->integer('amount')->default(0);

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
        Schema::dropIfExists('import_product_order_list_fees');
    }
}
