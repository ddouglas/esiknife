<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberBookmarkFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_bookmark_folders', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('folder_id');
            $table->string('name');
            $table->timestamps();

            $table->primary(['id', 'folder_id']);

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
        Schema::dropIfExists('member_bookmark_folders');
    }
}
