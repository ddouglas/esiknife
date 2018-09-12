<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnDaysToCompleteOnContracts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `contracts` CHANGE COLUMN `days_to_complete` `days_to_complete` INT NOT NULL AFTER `for_corporation`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `contracts` CHANGE COLUMN `days_to_complete` `days_to_complete` TINYINT NOT NULL AFTER `for_corporation`;");
    }
}
