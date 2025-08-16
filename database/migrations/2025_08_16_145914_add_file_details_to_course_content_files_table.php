<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileDetailsToCourseContentFilesTable extends Migration
{
    public function up()
    {
        Schema::table('course_content_files', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_type')->nullable()->after('file_name');
            $table->decimal('file_size', 10, 2)->nullable()->after('file_type');
        });
    }

    public function down()
    {
        Schema::table('course_content_files', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_type', 'file_size']);
        });
    }
}
