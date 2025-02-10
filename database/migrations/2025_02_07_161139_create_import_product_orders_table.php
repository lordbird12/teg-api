<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportProductOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_product_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();

            $table->integer('register_importer_id')->unsigned()->index();
            $table->foreign('register_importer_id')->references('id')->on('register_importers')->onDelete('cascade');

            $table->integer('store_id')->unsigned()->index();
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            
            $table->text('note')->nullable()->charset('utf8');

            $table->enum('status', ['import_document','waiting_document_check','waiting_tax_payment','in_process','completed'])->charset('utf8')->default('import_document');

            $table->string('invoice_file')->nullable();
            $table->string('packinglist_file')->nullable();
            $table->string('license_file')->nullable();
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
        Schema::dropIfExists('import_product_orders');
    }
}
