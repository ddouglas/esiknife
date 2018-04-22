<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberWalletJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_wallet_journals', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('ref_id');
            $table->string('ref_type');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('first_party_id')->nullable();
            $table->string('first_party_type')->nullable();
            $table->unsignedBigInteger('second_party_id')->nullable();
            $table->string('second_party_type')->nullable();
            $table->float('amount', 17,2)->nullable();
            $table->float('balance', 17,2)->nullable();
            $table->float('tax', 17,2)->nullable();
            $table->unsignedBigInteger('tax_reciever_id')->nullable();
            $table->unsignedBigInteger('ei_alliance_id')->nullable();
            $table->unsignedBigInteger('ei_corporation_id')->nullable();
            $table->unsignedBigInteger('ei_character_id')->nullable();
            $table->unsignedBigInteger('ei_contract_id')->nullable();
            $table->integer('ei_destroyed_ship_type_id')->nullable();
            $table->unsignedBigInteger('ei_job_id')->nullable();
            $table->unsignedBigInteger('ei_location_id')->nullable();
            $table->integer('ei_npc_id')->nullable();
            $table->string('ei_npc_name')->nullable();
            $table->integer('ei_planet_id')->nullable();
            $table->integer('ei_system_id')->nullable();
            $table->integer('ei_transaction_id')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamps();

            $table->primary(['id', 'ref_id']);

            $table->foreign('id')->references('id')->on('members')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_wallet_journals');
    }
}
