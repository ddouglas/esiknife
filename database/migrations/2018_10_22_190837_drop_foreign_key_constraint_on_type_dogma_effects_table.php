<?php

use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropForeignKeyConstraintOnTypeDogmaEffectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_NAME = ?", ['type_dogma_effects_type_id_foreign']);
        $result = collect($query);
        if ($result->isNotEmpty() && $result->count() == 1) {
            $result = $result->first();
            $key_name = $result->CONSTRAINT_NAME;
            DB::select("ALTER TABLE `type_dogma_effects` DROP FOREIGN KEY $key_name");
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
