<?php

namespace App\Http\Controllers\Api\Admin\StudentV2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Validator;

class V2CourseController extends Controller
{
    // 🔹 List all courses (paginated)
    public function index(Request $request)
    {
        $courses = Course::paginate($request->per_page ?? 10);
        return response()->json($courses);
    }

    // 🔹 Create a new course
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'recurring_month' => 'nullable|integer|min:1',
            'vat_tax_type' => 'nullable|in:percent,flat',
            'vat_tax_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course = Course::create($request->all());

        return response()->json(['message' => 'Course created successfully', 'course' => $course], 201);
    }

    // 🔹 Update an existing course
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'recurring_month' => 'nullable|integer|min:1',
            'vat_tax_type' => 'nullable|in:percent,flat',
            'vat_tax_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course->update($request->all());

        return response()->json(['message' => 'Course updated successfully', 'course' => $course]);
    }

    // 🔹 Delete a course
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    // 🔹 Update only price
    public function updatePrice(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'recurring_month' => 'nullable|integer|min:1',
            'vat_tax_type' => 'nullable|in:percent,flat',
            'vat_tax_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $course->update($request->only([
            'price',
            'recurring_month',
            'vat_tax_type',
            'vat_tax_value'
        ]));

        return response()->json(['message' => 'Price and recurring settings updated successfully', 'course' => $course]);
    }
}
