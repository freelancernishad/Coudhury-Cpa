<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            // foreign key drop করার জন্য dropConstrainedForeignId ব্যবহার
            // if (Schema::hasColumn('course_purchases', 'user_id')) {
            //     $table->dropConstrainedForeignId('user_id');
            // }

            // user_type ফিল্ড যোগ করা, default 'student'
            if (!Schema::hasColumn('course_purchases', 'user_type')) {
                $table->string('user_type')->default('student')->after('user_id');
            }
        });
    }

    public function down()
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            // user_type remove
            if (Schema::hasColumn('course_purchases', 'user_type')) {
                $table->dropColumn('user_type');
            }

            // foreign key recreate
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
    }
};
