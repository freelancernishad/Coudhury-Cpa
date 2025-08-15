<?php
// app/Http/Controllers/Api/Admin/StudentV2/V2CourseContentController.php
namespace App\Http\Controllers\Api\Admin\StudentV2;

use Illuminate\Http\Request;
use App\Models\CourseContent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Student;

class V2CourseContentController extends Controller
{
    // Get all contents for a course
    public function index(Request $request, $course_id)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');

    if (Auth::guard('admin')->check()) {
        // Admin guard হলে শুধু course_id দিয়ে ফিল্টার
        $query = CourseContent::where('course_id', $course_id);
    } else {
        $authStudentId = Auth::id(); // authenticated student

        // শুধু নিজের assign করা content দেখানো হবে
        $query = CourseContent::whereIn('id', function ($sub) use ($authStudentId) {
            $sub->select('course_content_id')
                ->from('course_content_student') // pivot table
                ->where('student_id', $authStudentId); // <-- student_id
        })->where('course_id', $course_id);

    }
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
            'students.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['course_id', 'name', 'description', 'link']);
        $content = CourseContent::create($data);

        if ($request->hasFile('file')) {
            $content->saveFile($request->file('file'));
        }

        // attach students
        if (!empty($request->students)) {
            $content->studentsV2()->attach($request->students);
        }

       // count students
    $content->students_count = $content->studentsV2()->count();

    // load studentsV2
    $content->load('studentsV2');

    // rename relation key for API response
    $contentArray = $content->toArray();
    $contentArray['students'] = $contentArray['students_v2'];
    unset($contentArray['students_v2']);

    return response()->json([
        'message' => 'Course content created successfully',
        'content' => $contentArray
    ], 201);
    }

    // Get students by content
    public function getStudentsByContent(Request $request, $contentId)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');

        $content = CourseContent::findOrFail($contentId);

        $studentsQuery = $content->studentsV2           ()
            ->select('students.id', 'students.client_id', 'students.name', 'students.email', 'students.profile_picture');

        if (!empty($search)) {
            $studentsQuery->where(function($query) use ($search) {
                $query->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.email', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->paginate($perPage);

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

    // Assign Students
    public function assignStudents(Request $request, $contentId)
    {
        $validator = Validator::make($request->all(), [
            'students' => 'required|array|min:1',
            'students.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = CourseContent::findOrFail($contentId);
        $content->studentsV2()->syncWithoutDetaching($request->students);

        return response()->json([
            'message' => 'Students assigned successfully',
            'content' => $content->load('students')
        ]);
    }

    // Remove Students
    public function removeStudents(Request $request, $contentId)
    {
        $validator = Validator::make($request->all(), [
            'students' => 'required|array|min:1',
            'students.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = CourseContent::findOrFail($contentId);
        $content->studentsV2()->detach($request->students);

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
            'file' => 'nullable|file|max:10240',
            'students' => 'nullable|array',
            'students.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content->fill($request->only(['name', 'description', 'link']));

        if ($request->hasFile('file')) {
            if ($content->file_path && Storage::exists($content->file_path)) {
                Storage::delete($content->file_path);
            }
            $content->saveFile($request->file('file'));
        }

        $content->save();

        if ($request->has('students')) {
            $content->studentsV2()->sync($request->students);
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
