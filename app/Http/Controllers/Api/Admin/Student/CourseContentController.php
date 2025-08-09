<?php
// app/Http/Controllers/Api/Admin/Student/CourseContentController.php
namespace App\Http\Controllers\Api\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CourseContentController extends Controller
{
    // Get all contents for a course
    public function index($course_id)
    {
        $contents = CourseContent::where('course_id', $course_id)->latest()->get();
        return response()->json($contents);
    }

    // Store new content
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['course_id', 'name', 'description', 'link']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('course_contents');
        }

        $content = CourseContent::create($data);

        return response()->json(['message' => 'Course content created successfully', 'content' => $content], 201);
    }

    // Update content
    public function update(Request $request, $id)
    {
        $content = CourseContent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $content->name = $request->name;
        }

        if ($request->has('description')) {
            $content->description = $request->description;
        }

        if ($request->has('link')) {
            $content->link = $request->link;
        }

        if ($request->hasFile('file')) {
            if ($content->file_path && Storage::exists($content->file_path)) {
                Storage::delete($content->file_path);
            }
            $content->file_path = $request->file('file')->store('course_contents');
        }

        $content->save();

        return response()->json(['message' => 'Course content updated successfully', 'content' => $content]);
    }

    // Delete content
    public function destroy($id)
    {
        $content = CourseContent::findOrFail($id);

        if ($content->file_path && Storage::exists($content->file_path)) {
            Storage::delete($content->file_path);
        }

        $content->delete();

        return response()->json(['message' => 'Course content deleted successfully']);
    }

    // Show single content
    public function show($id)
    {
        $content = CourseContent::findOrFail($id);
        return response()->json($content);
    }
}
