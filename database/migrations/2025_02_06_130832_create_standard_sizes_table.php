<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStandardSizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('standard_sizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();
            
            $table->string('name')->nullable();
            $table->decimal('weight', 8, 2)->default(0); // Supports weights with decimal values, e.g., 12345.67
            $table->decimal('width', 8, 2)->default(0); // Supports weights with decimal values, e.g., 12345.67
            $table->decimal('height', 8, 2)->default(0); // Supports heights with decimal values, e.g., 123.45
            $table->text('note')->nullable()->charset('utf8');
            
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
        Schema::dropIfExists('standard_sizes');
    }
}
