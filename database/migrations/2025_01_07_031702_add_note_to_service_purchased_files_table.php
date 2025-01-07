<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToServicePurchasedFilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            $table->text('note')->nullable()->after('service_name'); // Add the note column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            $table->dropColumn('note'); // Drop the note column if rolling back
        });
    }
}
