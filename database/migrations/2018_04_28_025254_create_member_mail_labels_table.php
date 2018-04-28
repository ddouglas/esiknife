<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberMailLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_mail_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('total_unread_count');
            $table->json('labels');
            $table->timestamps();

            $table->primary('id');

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
        Schema::dropIfExists('member_mail_labels');
    }
}
