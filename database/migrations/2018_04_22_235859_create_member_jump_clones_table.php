<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberJumpClonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_jump_clones', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('clone_id');
            $table->unsignedBigInteger('location_id');
            $table->enum('location_type', ['station', 'structure']);
            $table->json('implants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_jump_clones');
    }
}
