<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            // foreign key drop করার আগে চেক
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('course_purchases');

            if ($doctrineTable->hasForeignKey('course_purchases_user_id_foreign')) {
                $table->dropForeign('course_purchases_user_id_foreign');
            }

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

            // foreign key পুনরায় তৈরি
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
