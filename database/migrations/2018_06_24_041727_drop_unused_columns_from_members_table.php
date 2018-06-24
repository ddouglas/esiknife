<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUnusedColumnsFromMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumns('members', ['main', 'raw_hash', 'hash', 'refresh_token', 'token_error_count', 'disabled', 'disabled_reason', 'disabled_timestamp'])) {
                $table->dropColumn(['main', 'raw_hash', 'hash', 'refresh_token', 'token_error_count', 'disabled', 'disabled_reason', 'disabled_timestamp']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
