<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductDraftImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_draft_images', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_draft_id')->unsigned()->index();
            $table->foreign('product_draft_id')->references('id')->on('product_drafts')->onDelete('cascade');

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
        Schema::dropIfExists('product_draft_images');
    }
}
