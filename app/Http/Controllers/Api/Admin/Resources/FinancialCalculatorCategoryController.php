<?php

namespace App\Http\Controllers\Api\Admin\Resources;

use App\Http\Controllers\Controller;
use App\Models\FinancialCalculatorCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FinancialCalculatorCategoryController extends Controller
{
    public function index()
    {
        return response()->json(FinancialCalculatorCategory::withCount('calculators')->orderBy('order_index')->get());
    }

    public function publicIndex()
    {
        $categories = FinancialCalculatorCategory::with(['calculators' => function($query) {
            $query->select('id', 'name', 'slug', 'category_id')->where('is_active', true);
        }])
        ->where('is_active', true)
        ->orderBy('order_index')
        ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        $category = FinancialCalculatorCategory::create($data);
        return response()->json($category, 201);
    }

    public function show($id)
    {
        return response()->json(FinancialCalculatorCategory::with('calculators')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $category = FinancialCalculatorCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category->update($data);
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = FinancialCalculatorCategory::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
