<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropDescriptionColumnFromTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('characters', 'description')) {
            Schema::table('characters', function (Blueprint $table) {
                $table->dropColumn('bio');
            });
        }
        if (Schema::hasColumn('corporations', 'description')) {
            Schema::table('corporations', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (Schema::hasColumn('ancestries', 'description')) {
            Schema::table('ancestries', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (Schema::hasColumn('bloodlines', 'description')) {
            Schema::table('bloodlines', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (Schema::hasColumn('races', 'description')) {
            Schema::table('races', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (Schema::hasColumn('factions', 'description')) {
            Schema::table('factions', function (Blueprint $table) {
                $table->dropColumn('description');
            });
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
