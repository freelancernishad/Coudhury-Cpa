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
        $chats = Chat::where('user_id', Auth::id())->orderBy('id', 'desc')->get();
        return response()->json($chats, 200);
    }

    // Create a new chat
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255', // Can be renamed to 'title' or 'topic'
            'message' => 'required|string', // Initial message for the chat
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the chat
        $chat = Chat::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $chat->saveAttachment($request->file('attachment'));
        }

        return response()->json(['message' => 'Chat created successfully.', 'chat' => $chat], 201);
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
            'subject' => 'nullable|string|max:255', // Can be renamed to 'title' or 'topic'
            'message' => 'nullable|string', // Update the initial message
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the chat details
        $chat->update($request->only('subject', 'message'));

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $chat->saveAttachment($request->file('attachment'));
        }

        return response()->json(['message' => 'Chat updated successfully.', 'chat' => $chat], 200);
    }
}
