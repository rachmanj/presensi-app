<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ReportTemplate::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:report_templates,name'],
            'site_profile' => ['required', 'string', 'max:20'],
            'column_layout' => ['required', 'array'],
            'footer_config' => ['nullable', 'array'],
            'signature_config' => ['nullable', 'array'],
        ]);

        $template = ReportTemplate::create($data);

        return response()->json($template, 201);
    }

    public function show(ReportTemplate $reportTemplate): JsonResponse
    {
        return response()->json($reportTemplate);
    }

    public function update(Request $request, ReportTemplate $reportTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:50', 'unique:report_templates,name,'.$reportTemplate->id],
            'site_profile' => ['sometimes', 'string', 'max:20'],
            'column_layout' => ['sometimes', 'array'],
            'footer_config' => ['nullable', 'array'],
            'signature_config' => ['nullable', 'array'],
        ]);

        $reportTemplate->update($data);

        return response()->json($reportTemplate);
    }

    public function destroy(ReportTemplate $reportTemplate): JsonResponse
    {
        $reportTemplate->delete();

        return response()->json(null, 204);
    }
}
