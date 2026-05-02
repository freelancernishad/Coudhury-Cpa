<?php

namespace App\Http\Controllers\Api\Admin\Resources;

use App\Http\Controllers\Controller;
use App\Models\FinancialCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FinancialCalculatorController extends Controller
{
    /**
     * List all active calculators for the frontend
     */
    public function index()
    {
        $calculators = FinancialCalculator::with('category')->where('is_active', true)->get();
        return response()->json($calculators);
    }

    /**
     * CRUD: Store a new calculator
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:financial_calculator_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'inputs' => 'required|array',
            'formula' => 'nullable|string', // Now optional
            'results' => 'required|array|min:1', // At least one result required now
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        try {
            $calculator = FinancialCalculator::create($data);
            return response()->json($calculator, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * CRUD: Update a calculator
     */
    public function update(Request $request, $id)
    {
        $calculator = FinancialCalculator::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:financial_calculator_categories,id',
            'inputs' => 'sometimes|required|array',
            'formula' => 'nullable|string',
            'results' => 'sometimes|required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        try {
            $calculator->update($data);
            return response()->json($calculator);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update Database Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * CRUD: Delete a calculator
     */
    public function destroy($id)
    {
        $calculator = FinancialCalculator::findOrFail($id);
        $calculator->delete();
        return response()->json(['message' => 'Calculator deleted successfully']);
    }

    /**
     * Public: Get calculator details by ID or Slug
     */
    public function show($id)
    {
        $calculator = FinancialCalculator::with('category')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();
            
        return response()->json($calculator);
    }

    /**
     * Dynamic Calculation Engine
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string', // This is the slug
            'inputs' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = $request->input('type');
        $inputValues = $request->input('inputs');

        $calculator = FinancialCalculator::where('slug', $slug)->first();

        if (!$calculator) {
            return response()->json(['error' => 'Calculator not found'], 404);
        }

        try {
            $multiResults = [];
            
            // 1. Calculate the primary formula if it exists
            $primaryResult = null;
            if ($calculator->formula) {
                $primaryResult = $this->evaluateFormula($calculator->formula, $inputValues);
            }
            
            // 2. Calculate results breakdown
            if ($calculator->results && is_array($calculator->results)) {
                foreach ($calculator->results as $resConfig) {
                    $val = $this->evaluateFormula($resConfig['formula'], $inputValues);
                    $multiResults[] = [
                        'label' => $resConfig['label'],
                        'comment' => $resConfig['comment'] ?? '',
                        'value' => $val
                    ];
                }
            }

            return response()->json([
                'result' => $primaryResult,
                'multi_results' => $multiResults
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Calculation error: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Safe Formula Evaluator
     */
    private function evaluateFormula($formula, $vars)
    {
        if (empty($formula)) return 0;

        $evaluatedFormula = $formula;
        
        foreach ($vars as $key => $value) {
            $val = is_numeric($value) ? $value : 0;
            $evaluatedFormula = preg_replace('/\b' . preg_quote($key, '/') . '\b/', $val, $evaluatedFormula);
        }

        // Security check: Only allow math characters and functions
        $checkString = preg_replace('/pow|sqrt|abs|min|max/i', '', $evaluatedFormula);
        
        // Ensure no malicious PHP code or alphabets outside of math functions
        if (preg_match('/[a-z_]/i', $checkString)) {
            // If there's still text left, it might be an undefined variable or malicious
            // But we already replaced vars, so anything left is unknown
            // throw new \Exception("Invalid formula or missing variable: $evaluatedFormula");
        }

        $evaluatedFormula = str_replace('^', '**', $evaluatedFormula);

        try {
            // Basic math evaluation
            $result = eval("return $evaluatedFormula;");
            return is_numeric($result) ? $result : 0;
        } catch (\Throwable $t) {
            throw new \Exception("Mathematical error in [$formula]: " . $t->getMessage());
        }
    }
}
