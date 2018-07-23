<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id');
            $table->string('id', 16);
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->json('scopes');
            $table->timestamps();

            $table->primary('id');

            $table->foreign('creator_id')->references('id')->on('members')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::update("ALTER TABLE access_groups AUTO_INCREMENT = 10000000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_groups');
    }
}
