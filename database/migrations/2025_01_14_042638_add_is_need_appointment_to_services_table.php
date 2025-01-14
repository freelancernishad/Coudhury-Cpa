<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsNeedAppointmentToServicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_need_appointment')->default(false)->after('is_state_select');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_need_appointment');
        });
    }
}
