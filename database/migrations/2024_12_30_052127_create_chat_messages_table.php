<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id'); // Renamed from 'support_ticket_id'
            $table->unsignedBigInteger('admin_id')->nullable(); // Optional for chat
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message'); // Renamed from 'reply'
            $table->unsignedBigInteger('parent_id')->nullable(); // Renamed from 'reply_id' for nested messages
            $table->string('attachment')->nullable(); // Optional for chat
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('chat_messages')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
