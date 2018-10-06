<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ModifyAccessTokenColumnOnMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `members` CHANGE COLUMN `access_token` `access_token` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `clone_location_type`;");
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
