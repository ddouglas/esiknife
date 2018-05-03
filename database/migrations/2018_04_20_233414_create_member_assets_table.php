<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('type_id');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('location_id');
            $table->enum('location_type',['station', 'solar_system', 'other']);
            $table->string('location_flag');
            $table->boolean('is_singleton');
            $table->timestamps();

            $table->primary(['id', 'item_id']);

            $table->index('type_id');

            $table->foreign('id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_assets');
    }
}
