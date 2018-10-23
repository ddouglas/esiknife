<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFittingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fittings', function (Blueprint $table) {
            $table->unsignedInteger('id'); // Unique ID of the Fitting.
            $table->unsignedBigInteger('member_id'); // Unique ID of the Fitting.
            $table->unsignedInteger('type_id'); // ID of the Ship Type that the fitting is for
            $table->string('name'); // The Player assigned name of the Fitting
            $table->text('description'); // The player assigned description of the fitting
            $table->json('skills');
            $table->timestamps();

            $table->primary('id');
            $table->index('member_id');

            $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fittings');
    }
}
