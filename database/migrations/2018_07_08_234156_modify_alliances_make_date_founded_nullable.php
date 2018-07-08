<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyAlliancesMakeDateFoundedNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `alliances` CHANGE COLUMN `date_founded` `date_founded` TIMESTAMP NULL DEFAULT NULL AFTER `executor_corporation_id`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `alliances` CHANGE COLUMN `date_founded` `date_founded` TIMESTAMP NULL DEFAULT NULL AFTER `executor_corporation_id`;");
    }
}
