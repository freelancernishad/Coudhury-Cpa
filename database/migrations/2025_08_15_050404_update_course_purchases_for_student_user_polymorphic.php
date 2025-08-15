<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            // পুরানো foreign key drop
            $table->dropForeign(['user_id']);

            // user_type ফিল্ড যোগ করা, default 'student'
            $table->string('user_type')->default('student')->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            // user_type remove
            $table->dropColumn('user_type');

            // পুরানো foreign key পুনরায় তৈরি
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
