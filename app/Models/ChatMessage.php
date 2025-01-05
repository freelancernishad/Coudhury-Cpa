<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id', // Renamed from 'support_ticket_id'
        'admin_id', // Optional for chat
        'user_id',
        'message', // Renamed from 'reply'
        'parent_id', // Renamed from 'reply_id' for nested messages
        'attachment', // Optional for chat
    ];

    protected $with = [
        // 'admin',
        // 'user',
    ];

    // Relationship with the parent message (nested messages)
    public function parent()
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    // Relationship with child messages (nested messages)
    public function children()
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }

    // Relationship with the Chat
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    // Relationship with the Admin (optional)
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // Relationship with the User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Save the attachment for the chat message.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string File path of the uploaded attachment
     */
    public function saveAttachment($file)
    {
        $filePath = uploadFileToS3($file, 'attachments/chat_messages'); // Define the S3 directory
        $this->attachment = $filePath;
        $this->save();

        return $filePath;
    }
    
    protected $hidden = [
        'chat_id',
        'user_id',
        'admin_id',
    ];


    public function getSenderTypeAttribute()
    {
        if ($this->admin_id) {
            return 'admin';
        } elseif ($this->user_id) {
            return 'user';
        }

        return 'unknown';
    }
    protected $appends = ['sender_type']; // Add this line
}
