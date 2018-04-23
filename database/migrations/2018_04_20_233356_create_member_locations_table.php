<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->integer('solar_system_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->enum('location_type', ['station', 'structure', 'system'])->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->foreign('id')->references('id')->on('members')->onDelete('cascade')->onUpdate('cascade');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_locations');
    }
}
