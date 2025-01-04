<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('subject')->nullable(); // Can be renamed to 'title' or 'topic'
            $table->text('message'); // Can be renamed to 'initial_message' or 'content'
            $table->string('status')->default('open'); // Optional for chat
            $table->string('priority')->nullable(); // Optional for chat
            $table->string('attachment')->nullable(); // Optional for chat
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
