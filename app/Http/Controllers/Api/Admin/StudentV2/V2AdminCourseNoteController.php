<?php

namespace App\Http\Controllers\Api\Admin\StudentV2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseNote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class V2AdminCourseNoteController extends Controller
{
    // 🔹 Get all notes for a specific CoursePurchase
    public function index($course_purchase_id)
    {
        $notes = CourseNote::where('course_purchase_id', $course_purchase_id)->latest()->get();
        return response()->json($notes);
    }

    // 🔹 Store a new note (admin can add note for any course purchase)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_purchase_id' => 'required|exists:course_purchases,id',
            'note_text' => 'nullable|string',
            'note_file' => 'nullable|file|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only('course_purchase_id', 'note_text');

        if ($request->hasFile('note_file')) {
            $path = $request->file('note_file')->store('course_notes');
            $data['file_path'] = $path;
        }

        $note = CourseNote::create($data);

        return response()->json(['message' => 'Note created successfully', 'note' => $note], 201);
    }

    // 🔹 Update note
    public function update(Request $request, $id)
    {
        $note = CourseNote::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'note_text' => 'nullable|string',
            'note_file' => 'nullable|file|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('note_text')) {
            $note->note_text = $request->note_text;
        }

        if ($request->hasFile('note_file')) {
            if ($note->file_path && Storage::exists($note->file_path)) {
                Storage::delete($note->file_path);
            }
            $note->file_path = $request->file('note_file')->store('course_notes');
        }

        $note->save();

        return response()->json(['message' => 'Note updated successfully', 'note' => $note]);
    }

    // 🔹 Delete note
    public function destroy($id)
    {
        $note = CourseNote::findOrFail($id);

        if ($note->file_path && Storage::exists($note->file_path)) {
            Storage::delete($note->file_path);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }

    // 🔹 Show single note
    public function show($id)
    {
        $note = CourseNote::findOrFail($id);
        return response()->json($note);
    }
}
