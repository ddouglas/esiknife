<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('hash', 16);
            $table->string('name')->nullable();
            $table->json('scopes');
            $table->timestamps();

            $table->primary(['id', 'hash']);

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
        Schema::dropIfExists('member_urls');
    }
}
