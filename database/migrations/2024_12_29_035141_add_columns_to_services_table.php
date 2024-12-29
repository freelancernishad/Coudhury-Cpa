<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToServicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_select_multiple_child')->default(false);
            $table->boolean('is_add_on')->default(false);
            $table->boolean('is_state_select')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_select_multiple_child');
            $table->dropColumn('is_add_on');
            $table->dropColumn('is_state_select');
        });
    }
}
