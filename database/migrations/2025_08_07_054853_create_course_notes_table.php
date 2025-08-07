<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseNotesTable extends Migration
{
    public function up()
    {
        Schema::create('course_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_purchase_id')->constrained()->onDelete('cascade');
            $table->longText('note_text')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_notes');
    }
}
