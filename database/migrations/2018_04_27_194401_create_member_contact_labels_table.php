<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberContactLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_contact_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('label_id');
            $table->string('label_name');
            $table->timestamps();

            $table->primary(['id', 'label_id']);

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
        Schema::dropIfExists('member_contact_labels');
    }
}
