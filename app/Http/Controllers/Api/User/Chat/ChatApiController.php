<?php

namespace App\Http\Controllers\Api\User\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatApiController extends Controller
{
    // Get all chats for the authenticated user
    public function index()
    {
        $chats = Chat::select('id','user_id','message','attachment','created_at')->where('user_id', Auth::id())->orderBy('id', 'desc')->get();
        return response()->json($chats, 200);
    }

    // Create a new chat
    public function store(Request $request)
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

        // Get the authenticated user
        $user = Auth::user();

        // Check if the user has any existing chats
        $existingChat = Chat::where('user_id', $user->id)->first();

        if ($existingChat) {
            // If a chat exists, create a reply to that chat
            $messageData = [
                'message' => $request->message,
                'parent_id' => $request->parent_id, // Set the parent message ID if provided
                'user_id' => $user->id, // Associate the message with the user
            ];

            // Create a new message associated with the existing chat
            $message = $existingChat->messages()->create($messageData);

            // Handle attachment if present
            if ($request->hasFile('attachment')) {
                $message->saveAttachment($request->file('attachment'));
            }

            return response()->json([
                'message' => 'Reply sent successfully.',
                'chat_message' => $message
            ], 200);
        } else {
            // If no chat exists, create a new chat
            $chat = Chat::create([
                'user_id' => $user->id,
                'message' => $request->message,
            ]);

            // Create the initial message for the new chat
            $messageData = [
                'message' => $request->message,
                'user_id' => $user->id, // Associate the message with the user
            ];

            $message = $chat->messages()->create($messageData);

            // Handle attachment if present
            if ($request->hasFile('attachment')) {
                $message->saveAttachment($request->file('attachment'));
            }

            return response()->json([
                'message' => 'Chat created successfully.',
                'chat' => $chat,
                'chat_message' => $message
            ], 201);
        }
    }
    // Show a specific chat
    public function show(Chat $chat)
    {
        // Ensure the chat belongs to the authenticated user
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json($chat, 200);
    }

    // Update a chat (if needed)
    public function update(Request $request, Chat $chat)
    {
        // Ensure the chat belongs to the authenticated user
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            // 'subject' => 'nullable|string|max:255', // Can be renamed to 'title' or 'topic'
            'message' => 'nullable|string', // Update the initial message
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the chat details
        $chat->update($request->only('message'));

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $chat->saveAttachment($request->file('attachment'));
        }

        return response()->json(['message' => 'Chat updated successfully.', 'chat' => $chat], 200);
    }
}
