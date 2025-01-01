<?php

namespace App\Http\Controllers\Api\Admin\Chat;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AdminChatApiController extends Controller
{
    // Get all chats for admin
    public function index()
    {
        $chats = Chat::with('user')->latest()->get();
        return response()->json($chats);
    }

    // View a specific chat
    public function show($id)
    {
        $chat = Chat::with(['user', 'messages'])->findOrFail($id);
        return response()->json($chat);
    }

    // Send a message in a chat
    public function sendMessage(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:chat_messages,id', // Check if the parent message exists
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the chat by ID
        $chat = Chat::findOrFail($id);

        // Prepare message data
        $messageData = [
            'message' => $request->message,
            'parent_id' => $request->parent_id, // Set the parent message ID if provided
        ];

        // Check if the logged-in user is an admin
        if (auth()->guard('admin')->check()) {
            $messageData['admin_id'] = auth()->guard('admin')->id();
        } else {
            $messageData['user_id'] = auth()->guard('user')->id();
        }

        // Create a new message associated with the chat
        $message = $chat->messages()->create($messageData);

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $message->saveAttachment($request->file('attachment'));
        }

        return response()->json([
            'message' => 'Message sent successfully.',
            'chat_message' => $message
        ], 200);
    }

    // Update chat status (optional, if you have a status field in the Chat model)
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:open,closed,pending', // Define allowed statuses
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $chat = Chat::findOrFail($id);
        $chat->status = $request->status;
        $chat->save();

        return response()->json(['message' => 'Chat status updated successfully.'], 200);
    }
}
