<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('service_purchased', function (Blueprint $table) {
            $table->text('admin_private_note')->nullable()->after('admin_note');
        });
    }

    public function down()
    {
        Schema::table('service_purchased', function (Blueprint $table) {
            $table->dropColumn('admin_private_note');
        });
    }
};
