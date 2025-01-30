<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject', // Can be renamed to 'title' or 'topic' for chat
        'message', // Can be renamed to 'initial_message' or 'content'
        'status',
        'priority', // Optional for chat
        'attachment', // Optional for chat
    ];

    protected $with = [
        'user',
        'messages', // Renamed from 'replies'
    ];

    // Relationship with the User
    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'client_id', 'name', 'profile_picture');
    }

    // Relationship with ChatMessages (renamed from 'replies')
    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->select('id', 'chat_id','user_id','admin_id', 'message', 'attachment', 'created_at');
    }

    /**
     * Save the attachment for the chat.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string File path of the uploaded attachment
     */
    public function saveAttachment($file)
    {
        $filePath = uploadFileToS3($file, 'attachments/chats'); // Define the S3 directory
        $this->attachment = $filePath;
        $this->save();

        return $filePath;
    }
}
