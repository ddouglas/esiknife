<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberBookmarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_bookmarks', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('bookmark_id');
            $table->unsignedInteger('folder_id')->nullable();
            $table->timestamp('created')->nullable();
            $table->string('label');
            $table->text('notes')->nullable();
            $table->unsignedInteger('location_id');
            $table->enum('location_type', ['system', 'constellation', 'region']);
            $table->unsignedBigInteger('creator_id');
            $table->enum('creator_type', ['character', 'corporation']);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->json('coordinates')->nullable();
            $table->timestamps();

            $table->primary(['id', 'bookmark_id']);

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
        Schema::dropIfExists('member_bookmarks');
    }
}
