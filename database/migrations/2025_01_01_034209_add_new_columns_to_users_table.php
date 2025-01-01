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
        Schema::table('users', function (Blueprint $table) {
            $table->string('client_id')->unique()->after('id');
            $table->string('status')->default('active')->after('client_id');
            $table->string('nid_no')->nullable()->after('status');
            $table->string('address_line1')->nullable()->after('nid_no');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('phone')->nullable()->after('address_line2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'status', 'nid_no', 'address_line1', 'address_line2', 'phone']);
        });
    }
};
