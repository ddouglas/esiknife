<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDisabledColumnsToMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('disabled_timestamp')->nullable()->after('refresh_token');
            $table->text('disabled_reason')->nullable()->after('refresh_token');
            $table->boolean('disabled')->default(0)->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumns('members', ['disabled', 'disabled_reason', 'disabled_timestamp'])) {
                $table->dropColumn(['disabled', 'disabled_reason', 'disabled_timestamp']);
            }
        });
    }
}
