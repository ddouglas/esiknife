<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_headers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('sender_id');
            $table->enum('sender_type', ['character', 'mailing_list']);
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_on_mailing_list')->nullable()->default(0);
            $table->unsignedBigInteger('mailing_list_id')->nullable();
            $table->boolean('is_ready')->default(0);
            $table->timestamp('sent')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->index('sender_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_headers');
    }
}
