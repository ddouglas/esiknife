<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->unsignedInteger('mail_id');
            $table->unsignedBigInteger('recipient_id');
            $table->enum('recipient_type', ['character', 'corporation', 'alliance', 'mailing_list']);
            $table->timestamps();

            $table->primary(['mail_id', 'recipient_id']);

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
        Schema::dropIfExists('mail_recipients');
    }
}
