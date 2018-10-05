<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropIsBlockColumnFromContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member_contacts', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_contacts', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(0)->nullable()->after('standing');
        });
    }
}
