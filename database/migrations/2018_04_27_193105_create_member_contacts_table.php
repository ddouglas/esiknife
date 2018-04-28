<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('contact_id');
            $table->enum('contact_type', ['character', 'corporation', 'alliance', 'faction']);
            $table->json('label_ids')->nullable();
            $table->float('standing');
            $table->boolean('is_blocked')->default(0)->nullable();
            $table->boolean('is_watched')->default(0)->nullable();

            $table->primary(['id', 'contact_id']);

            $table->timestamps();

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
        Schema::dropIfExists('member_contacts');
    }
}
