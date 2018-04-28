<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('issuer_id');
            $table->unsignedBigInteger('issuer_corporation_id');
            $table->unsignedBigInteger('assignee_id');
            $table->enum('assignee_type', ['character','corporation'])->nullable();
            $table->unsignedBigInteger('acceptor_id')->nullable();
            $table->enum('acceptor_type', ['character','corporation'])->nullable();
            $table->string('title')->nullable();
            $table->enum('type', ['unknown', 'item_exchange', 'auction', 'courier', 'loan']);
            $table->enum('status', ['outstanding', 'in_progress', 'finished_issuer', 'finished_contractor', 'finished', 'cancelled', 'rejected', 'failed', 'deleted', 'reversed']);
            $table->enum('availability', ['public', 'personal', 'corporation', 'alliance']);
            $table->boolean('for_corporation');
            $table->tinyInteger('days_to_complete');
            $table->float('collateral',17,2)->nullable();
            $table->float('price',17,2)->nullable();
            $table->float('reward',17,2)->nullable();
            $table->float('volume',17,2)->nullable();
            $table->unsignedBigInteger('start_location');
            $table->enum('start_location_type', ['station', 'structure']);
            $table->unsignedBigInteger('end_location');
            $table->enum('end_location_type', ['station', 'structure']);
            $table->timestamp('date_accepted')->nullable();
            $table->timestamp('date_completed')->nullable();
            $table->timestamp('date_expired')->nullable();
            $table->timestamp('date_issued')->nullable();
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
