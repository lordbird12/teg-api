<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportProductOrderListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_product_order_lists', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('import_product_order_id')->unsigned()->index();
            $table->foreign('import_product_order_id')->references('id')->on('import_product_orders')->onDelete('cascade');

            $table->integer('product_type_id')->unsigned()->index();
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('cascade');
            
            $table->text('product_name')->nullable()->charset('utf8');
            $table->text('track_no')->nullable()->charset('utf8');
            
            $table->decimal('weight', 8, 2)->default(0); // Supports weights with decimal values, e.g., 12345.67
            $table->decimal('width', 8, 2)->default(0); // Supports weights with decimal values, e.g., 12345.67
            $table->decimal('height', 8, 2)->default(0); // Supports heights with decimal values, e.g., 123.45
            $table->decimal('long', 8, 2)->default(0); // Supports heights with decimal values, e.g., 123.45

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
        Schema::dropIfExists('import_product_order_lists');
    }
}
