<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveTimestampsFromTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('type_dogma_attributes', 'created_at') && Schema::hasColumn('type_dogma_attributes', 'updated_at')) {
            DB::statement('ALTER TABLE `type_dogma_attributes` DROP COLUMN `created_at`, DROP COLUMN `updated_at`;');
        }
        if (Schema::hasColumn('type_dogma_effects', 'created_at') && Schema::hasColumn('type_dogma_effects', 'updated_at')) {
            DB::statement('ALTER TABLE `type_dogma_effects` DROP COLUMN `created_at`, DROP COLUMN `updated_at`;');
        }
        if (Schema::hasColumn('type_skillz', 'created_at') && Schema::hasColumn('type_skillz', 'updated_at')) {
            DB::statement('ALTER TABLE `type_skillz` DROP COLUMN `created_at`, DROP COLUMN `updated_at`;');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
