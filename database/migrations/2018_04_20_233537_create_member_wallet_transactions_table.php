<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('client_id');
            $table->enum('client_type', ['character', 'corporation']);
            $table->boolean('is_buy');
            $table->boolean('is_personal');
            $table->unsignedBigInteger('journal_ref_id');
            $table->unsignedBigInteger('location_id');
            $table->enum('location_id', ['station', 'structure']);
            $table->integer('quantity');
            $table->integer('type_id');
            $table->float('unit_price',17,2);
            $table->timestamp('date')->nullable();
            $table->timestamps();

            $table->primary(['id','transaction_id']);

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
        Schema::dropIfExists('member_wallet_transactions');
    }
}
