<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Site::orderBy('code')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:sites,code'],
            'name' => ['required', 'string', 'max:150'],
            'profile' => ['required', 'in:coal,office,support'],
            'base_present_code' => ['required', 'string', 'max:20'],
            'active' => ['boolean'],
        ]);

        $site = Site::create($data);

        return response()->json($site, 201);
    }

    public function show(Site $site): JsonResponse
    {
        return response()->json($site);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:10', 'unique:sites,code,'.$site->id],
            'name' => ['sometimes', 'string', 'max:150'],
            'profile' => ['sometimes', 'in:coal,office,support'],
            'base_present_code' => ['sometimes', 'string', 'max:20'],
            'active' => ['boolean'],
        ]);

        $site->update($data);

        return response()->json($site);
    }

    public function destroy(Site $site): JsonResponse
    {
        $site->delete();

        return response()->json(null, 204);
    }
}
