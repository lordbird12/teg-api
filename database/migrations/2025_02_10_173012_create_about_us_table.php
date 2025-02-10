<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAboutUsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('about_us', function (Blueprint $table) {
            $table->increments('id');
            
            $table->text('detail')->nullable()->charset('utf8');
            $table->text('title_box')->charset('utf8');
            $table->text('body_box')->charset('utf8');
            $table->text('footer_box')->charset('utf8');

            $table->string('phone', 100)->charset('utf8')->nullable();
            $table->string('email', 100)->charset('utf8')->nullable();
            $table->string('wechat', 100)->charset('utf8')->nullable();
            $table->string('line', 100)->charset('utf8')->nullable();
            $table->string('facebook', 100)->charset('utf8')->nullable();

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
        Schema::dropIfExists('about_us');
    }
}
