<?php
// app/Http/Controllers/Api/Admin/Student/CourseContentController.php
namespace App\Http\Controllers\Api\Admin\Student;

use Illuminate\Http\Request;
use App\Models\CourseContent;
use App\Models\CourseContentFile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CourseContentController extends Controller
{
    // Get all contents for a course

public function index(Request $request, $course_id)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');

    if (Auth::guard('admin')->check()) {
        // Admin guard হলে শুধু course_id দিয়ে ফিল্টার
        $query = CourseContent::with('files', 'links')->where('course_id', $course_id);
    } else {
        // অন্য guard হলে শুধু নিজের assign করা content
        $authUserId = Auth::id();

        $query = CourseContent::with('files', 'links')->whereIn('id', function ($sub) use ($authUserId) {
                $sub->select('course_content_id')
                    ->from('course_content_user')
                    ->where('user_id', $authUserId);
            })
            ->where('course_id', $course_id);
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
            'link' => 'nullable', // string or array allowed
            'file' => 'nullable', // single or array of files
            'file.*' => 'file|max:10240', // validate each file
            'students' => 'nullable|array',
            'students.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create course content
        $data = $request->only(['course_id', 'name', 'description']);
        $content = CourseContent::create($data);

        // Handle links (can be string or array)
        if ($request->filled('link')) {
            $links = is_array($request->link) ? $request->link : [$request->link];
            foreach ($links as $link) {
                CourseContentFile::create([
                    'course_content_id' => $content->id,
                    'link' => $link,
                ]);
            }
        }

        // Handle files (can be single or multiple)
        if ($request->hasFile('file')) {
            $files = is_array($request->file('file')) ? $request->file('file') : [$request->file('file')];

            foreach ($files as $file) {
                $contentFile = new \App\Models\CourseContentFile();
                $contentFile->course_content_id = $content->id;
                $contentFile->saveFile($file);
            }
        }

        // Attach students
        if (!empty($request->students)) {
            $content->students()->attach($request->students);
        }

        return response()->json([
            'message' => 'Course content created successfully',
            'content' => $content->load('students', 'files','links')
        ], 201);
    }






    public function getStudentsByContent(Request $request, $contentId)
    {
        $perPage = $request->query('per_page', 10); // ডিফল্ট 10
        $search = $request->query('search'); // search keyword

        $content = CourseContent::findOrFail($contentId);

        $studentsQuery = $content->students()
            ->select('users.id', 'users.client_id', 'users.name', 'users.email', 'users.profile_picture');

        // যদি search থাকে তাহলে filter করো
        if (!empty($search)) {
            $studentsQuery->where(function($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
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

        // link can be string or array
        'link' => 'nullable',
        'link.*' => 'nullable|url',

        // file can be single or array
        'file' => 'nullable',
        'file.*' => 'file|max:10240',

        'students' => 'nullable|array',
        'students.*' => 'exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Update basic fields
    $content->fill($request->only(['name', 'description']));
    $content->save();

    // ✅ Delete old files and links
    foreach ($content->courseContentFiles as $file) {
        if ($file->file_path && Storage::exists($file->file_path)) {
            Storage::delete($file->file_path);
        }
        $file->delete();
    }

    // ✅ Add new links
    if ($request->has('link')) {
        $links = $request->input('link');
        $links = is_array($links) ? $links : [$links];

        foreach ($links as $link) {
            if ($link) {
                CourseContentFile::create([
                    'course_content_id' => $content->id,
                    'link' => $link,
                ]);
            }
        }
    }

    // ✅ Add new files
    if ($request->hasFile('file')) {
        $files = $request->file('file');
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            $contentFile = new \App\Models\CourseContentFile();
            $contentFile->course_content_id = $content->id;
            $contentFile->saveFile($file);
        }
    }

    // ✅ Sync students
    if ($request->has('students')) {
        $content->students()->sync($request->students);
    }

    return response()->json([
        'message' => 'Course content updated successfully',
        'content' => $content->load(['students', 'files', 'links']),
    ]);
}

    // Delete content
public function destroy($id)
{
    $content = CourseContent::with(['files', 'links'])->findOrFail($id);

    // ✅ Delete files from storage
    foreach ($content->files as $file) {
        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete(); // Delete file record from DB
    }

    // ✅ Delete links from DB
    foreach ($content->links as $link) {
        $link->delete();
    }

    // ✅ Finally delete the course content
    $content->delete();

    return response()->json([
        'message' => 'Course content and its associated files & links deleted successfully.'
    ]);
}

    public function destroyContentFile($id)
    {
        $file = CourseContentFile::findOrFail($id);

        // যদি এটা একটি ফাইল হয় (not just a link)
        // if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
        //     Storage::disk('public')->delete($file->file_path);
        // }

        // ডাটাবেস থেকে রেকর্ড ডিলিট
        $file->delete();

        return response()->json([
            'message' => 'Content file/link deleted successfully.'
        ]);
    }
    // Show single content
    public function show($id)
    {
        $content = CourseContent::with('files', 'links')->findOrFail($id);
        return response()->json($content);
    }
}
