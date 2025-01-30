<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminIdToServicePurchasedFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            // Add the 'admin_id' column
            $table->unsignedBigInteger('admin_id')->nullable()->after('note');

            // Optional: If you want to add a foreign key constraint for 'admin_id'
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            // Remove the 'admin_id' column
            $table->dropColumn('admin_id');
        });
    }
}
