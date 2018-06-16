<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyValueColumnOnTypeDogmaAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('type_dogma_attributes', function (Blueprint $table) {
            $table->float('value', 17, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('type_dogma_attributes', function (Blueprint $table) {
            $table->float('value', 10, 4)->change();
        });
    }
}
