<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberMailHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_mail_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('mail_id');
            $table->string('labels')->nullable();
            $table->boolean('is_read')->nullable();

            $table->primary(['member_id', 'mail_id']);

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('mail_id')->references('id')->on('mail_headers')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_mail_headers');
    }
}
