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
public function index(Request $request, $course_id)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search'); // search keyword

    $query = CourseContent::where('course_id', $course_id);

    // যদি search থাকে তাহলে name filter করা হবে
    if ($search) {
        $query->where('name', 'like', "%{$search}%");
    }

    $contents = $query->latest()->paginate($perPage);

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
            'students' => 'nullable|array',
            'students.*' => 'exists:users,id' // প্রতিটি student আইডি valid কিনা
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['course_id', 'name', 'description', 'link']);







        $content = CourseContent::create($data);


        // যদি ফাইল থাকে তাহলে saveFile() ব্যবহার করো
        if ($request->hasFile('file')) {
            $content->saveFile($request->file('file')); // S3 upload + file_path update
        }



        // যদি students array থাকে তাহলে attach করো
        if (!empty($request->students)) {
            $content->students()->attach($request->students);
        }

        return response()->json([
            'message' => 'Course content created successfully',
            'content' => $content->load('students') // students relationship সহ রিটার্ন করব
        ], 201);

    }


    public function getStudentsByContent(Request $request, $contentId)
    {
        $perPage = $request->query('per_page', 10); // ডিফল্ট 10 টি

        $content = CourseContent::findOrFail($contentId);

        $students = $content->students()
            ->select('users.id', 'users.client_id', 'users.name', 'users.email', 'users.profile_picture')
            ->paginate($perPage);

        return response()->json([
            'content_id' => $content->id,
            'content_name' => $content->name,
            'total_students' => $students->total(),
            'students' => $students->items(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'last_page' => $students->lastPage(),
                'total' => $students->total(),
            ]
        ]);
    }





        // 2️⃣ Assign Students to Existing Content
    public function assignStudents(Request $request, $contentId)
    {
        $validator = Validator::make($request->all(), [
            'students' => 'required|array|min:1',
            'students.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = CourseContent::findOrFail($contentId);

        // আগের সাথে merge হয়ে যাবে
        $content->students()->syncWithoutDetaching($request->students);

        return response()->json([
            'message' => 'Students assigned successfully',
            'content' => $content->load('students')
        ]);
    }

    // 3️⃣ Remove Students from Content
    public function removeStudents(Request $request, $contentId)
    {
        $validator = Validator::make($request->all(), [
            'students' => 'required|array|min:1',
            'students.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = CourseContent::findOrFail($contentId);

        $content->students()->detach($request->students);

        return response()->json([
            'message' => 'Students removed successfully',
            'content' => $content->load('students')
        ]);
    }



    // Update content
   public function update(Request $request, $id)
    {
        $content = CourseContent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
            'file' => 'nullable|file|max:10240', // 10MB
            'students' => 'nullable|array',
            'students.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update basic fields if present
        $content->fill($request->only(['name', 'description', 'link']));

        // Handle file upload
        if ($request->hasFile('file')) {
            if ($content->file_path && Storage::exists($content->file_path)) {
                Storage::delete($content->file_path);
            }
            $content->saveFile($request->file('file')); // Assuming saveFile() handles S3 upload and updates file_path
        }

        $content->save();

        // Sync students if provided
        if ($request->has('students')) {
            $content->students()->sync($request->students);
        }

        return response()->json([
            'message' => 'Course content updated successfully',
            'content' => $content->load('students')
        ]);
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
