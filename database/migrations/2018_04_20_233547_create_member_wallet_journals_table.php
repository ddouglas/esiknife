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
            $table->unsignedBigInteger('id')->comment("ID of the Member the Journal Entry Belongs To");
            $table->unsignedBigInteger('journal_id')->comment("Unique journal reference ID");
            $table->string('ref_type')->comment("Type of Journal Entry");
            $table->unsignedBigInteger('context_id')->nullable()->comment("An ID that gives extra context to the particular transaction. Because of legacy reasons the context is completely different per ref_type and means different things. It is also possible to not have a context_id");
            $table->enum('context_type', [
                'structure_id', 'station_id', 'market_transaction_id', 'character_id', 'corporation_id', 'alliance_id', 'eve_system', 'industry_job_id', 'contract_id', 'planet_id', 'system_id', 'type_id'
            ])->nullable()->comment("The type of the given context_id if present");
            $table->text('description')->comment("The reason for the transaction, mirrors what is seen in the client");
            $table->timestamp('date')->nullable()->comment("Date and time of transaction");
            $table->string('reason')->nullable()->comment("The user stated reason for the transaction.");
            $table->unsignedBigInteger('first_party_id')->nullable()->comment("The id of the first party involved in the transaction. This attribute has no consistency and is different or non existant for particular ref_types. The description attribute will help make sense of what this attribute means. For more info about the given ID it can be dropped into the /universe/names/ ESI route to determine its type and name");
            $table->string('first_party_type')->nullable()->comment("Self Derived Type. Used for Polymophic Relations so that the correct model can be queried and a name outputted");
            $table->unsignedBigInteger('second_party_id')->nullable()->comment("The id of the second party involved in the transaction. This attribute has no consistency and is different or non existant for particular ref_types. The description attribute will help make sense of what this attribute means. For more info about the given ID it can be dropped into the /universe/names/ ESI route to determine its type and name");
            $table->string('second_party_type')->nullable()->comment("Self Derived Type. Used for Polymophic Relations so that the correct model can be queried and a name outputted");
            $table->float('amount', 17,2)->nullable()->comment("The amount of ISK given or taken from the wallet as a result of the given transaction. Positive when ISK is deposited into the wallet and negative when ISK is withdrawn");
            $table->float('balance', 17,2)->nullable()->comment("Wallet balance after transaction occurred");
            $table->float('tax', 17,2)->nullable()->comment("Tax amount received. Only applies to tax related transactions");
            $table->unsignedBigInteger('tax_receiver_id')->nullable()->comment("The corporation ID receiving any tax paid. Only applies to tax related transactions");

            $table->timestamps();

            $table->primary(['id', 'journal_id']);

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
