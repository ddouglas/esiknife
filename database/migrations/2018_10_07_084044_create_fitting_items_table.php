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

            $table->primary(['fitting_id', 'type_id', 'flag', 'quantity'], 'fitting_items_primary_key');

            $table->foreign('fitting_id')->references('fitting_id')->on('fitting')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('type_id')->references('type_id')->on('types')->onUpdate('cascade')->onDelete('cascade');
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
