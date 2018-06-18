<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsBlueprintCopyToMemberAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member_assets', function (Blueprint $table) {
            $table->boolean('is_blueprint_copy')->default(0)->after('is_singleton');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_assets', function (Blueprint $table) {
            $table->dropColumn('is_blueprint_copy');
        });
    }
}
