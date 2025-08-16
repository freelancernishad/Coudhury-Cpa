<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 🔹 Remove 'link' and 'file_path' from 'course_contents'
        Schema::table('course_contents', function (Blueprint $table) {
            $table->dropColumn(['link', 'file_path']);
        });

        // 🔹 Create 'course_content_files' table
        Schema::create('course_content_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_content_id')->constrained()->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // 🔄 Revert: add the columns back
        Schema::table('course_contents', function (Blueprint $table) {
            $table->string('link')->nullable();
            $table->string('file_path')->nullable();
        });

        // 🔄 Drop the new table
        Schema::dropIfExists('course_content_files');
    }
};
