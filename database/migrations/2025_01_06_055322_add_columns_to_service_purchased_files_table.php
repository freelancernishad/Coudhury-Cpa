<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            // Add new columns
            $table->string('folder_name')->nullable()->after('mime_type');
            $table->string('year')->nullable()->after('folder_name');
            $table->string('month')->nullable()->after('year');
            $table->string('date')->nullable()->after('month');
            $table->string('service_name')->nullable()->after('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_purchased_files', function (Blueprint $table) {
            // Drop the columns if the migration is rolled back
            $table->dropColumn(['folder_name', 'year', 'month', 'date', 'service_name']);
        });
    }
};
