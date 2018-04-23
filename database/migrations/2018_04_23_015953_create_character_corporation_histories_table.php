<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCharacterCorporationHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_corporation_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('record_id');
            $table->unsignedInteger('corporation_id');
            $table->boolean('is_deleted')->default(0);
            $table->timestamp('start_date');
            $table->timestamps();

            $table->primary(['id', 'record_id']);

            $table->foreign('id')->references('id')->on('characters')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('character_corporation_histories');
    }
}
