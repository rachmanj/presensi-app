<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MatrixRule;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatrixRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MatrixRule::with(['homeSite', 'visitSite'])->orderBy('home_site_code');

        if ($request->filled('home_site_code')) {
            $query->where('home_site_code', $request->home_site_code);
        }

        return response()->json($query->get());
    }

    public function grid(): JsonResponse
    {
        $sites = Site::where('active', true)->orderBy('code')->pluck('code');
        $rules = MatrixRule::current()->get()->keyBy(fn ($r) => "{$r->home_site_code}:{$r->visit_site_code}");

        $grid = [];
        foreach ($sites as $home) {
            $row = ['home_site_code' => $home, 'cells' => []];
            foreach ($sites as $visit) {
                $rule = $rules->get("{$home}:{$visit}");
                $row['cells'][$visit] = $rule ? [
                    'id' => $rule->id,
                    'code' => $rule->code,
                    'effective_from' => $rule->effective_from?->toDateString(),
                    'effective_to' => $rule->effective_to?->toDateString(),
                ] : null;
            }
            $grid[] = $row;
        }

        return response()->json(['sites' => $sites, 'grid' => $grid]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'home_site_code' => ['required', 'string', 'max:10', 'exists:sites,code'],
            'visit_site_code' => ['required', 'string', 'max:10', 'exists:sites,code'],
            'code' => ['required', 'string', 'max:20'],
            'priority' => ['integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $rule = MatrixRule::create($data);

        return response()->json($rule->load(['homeSite', 'visitSite']), 201);
    }

    public function show(MatrixRule $matrixRule): JsonResponse
    {
        return response()->json($matrixRule->load(['homeSite', 'visitSite']));
    }

    public function update(Request $request, MatrixRule $matrixRule): JsonResponse
    {
        $data = $request->validate([
            'home_site_code' => ['sometimes', 'string', 'max:10', 'exists:sites,code'],
            'visit_site_code' => ['sometimes', 'string', 'max:10', 'exists:sites,code'],
            'code' => ['sometimes', 'string', 'max:20'],
            'priority' => ['integer'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $matrixRule->update($data);

        return response()->json($matrixRule->load(['homeSite', 'visitSite']));
    }

    public function destroy(MatrixRule $matrixRule): JsonResponse
    {
        $matrixRule->delete();

        return response()->json(null, 204);
    }
}
