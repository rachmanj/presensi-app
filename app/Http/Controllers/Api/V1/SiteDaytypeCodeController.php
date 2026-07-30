<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteDaytypeCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteDaytypeCodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteDaytypeCode::with('site')->orderBy('site_code');

        if ($request->filled('site_code')) {
            $query->where('site_code', $request->site_code);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_code' => ['required', 'string', 'max:10', 'exists:sites,code'],
            'day_type' => ['required', 'in:workday,off,day6,day7_holiday,standby'],
            'shift' => ['string', 'max:20'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $record = SiteDaytypeCode::create($data);

        return response()->json($record->load('site'), 201);
    }

    public function show(SiteDaytypeCode $siteDaytypeCode): JsonResponse
    {
        return response()->json($siteDaytypeCode->load('site'));
    }

    public function update(Request $request, SiteDaytypeCode $siteDaytypeCode): JsonResponse
    {
        $data = $request->validate([
            'site_code' => ['sometimes', 'string', 'max:10', 'exists:sites,code'],
            'day_type' => ['sometimes', 'in:workday,off,day6,day7_holiday,standby'],
            'shift' => ['string', 'max:20'],
            'code' => ['sometimes', 'string', 'max:20'],
        ]);

        $siteDaytypeCode->update($data);

        return response()->json($siteDaytypeCode->load('site'));
    }

    public function destroy(SiteDaytypeCode $siteDaytypeCode): JsonResponse
    {
        $siteDaytypeCode->delete();

        return response()->json(null, 204);
    }
}
