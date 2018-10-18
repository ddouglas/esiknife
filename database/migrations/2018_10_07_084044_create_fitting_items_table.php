<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFittingItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fitting_items', function (Blueprint $table) {
            $table->unsignedInteger('fitting_id');
            $table->unsignedInteger('type_id');
            $table->unsignedInteger('flag');
            $table->unsignedInteger('quantity');

            $table->primary(['fitting_id', 'type_id', 'flag']);

            $table->foreign('fitting_id')->references('id')->on('fittings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('types')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fitting_items');
    }
}
