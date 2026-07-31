<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMap;
use App\Models\FingerprintScan;
use App\Models\HeroEmployeeCache;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeMapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeMap::with('site')->orderBy('fingerprint_nip');

        if ($request->filled('site_code')) {
            $query->where('site_code', $request->site_code);
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $paginated = $query->paginate($request->integer('per_page', 50));
        $niks = collect($paginated->items())->pluck('nik')->filter();
        $balances = HeroEmployeeCache::whereIn('nik', $niks)->pluck('leave_balance', 'nik');

        $paginated->getCollection()->transform(function ($map) use ($balances) {
            $map->leave_balance = $balances[$map->nik] ?? null;

            return $map;
        });

        return response()->json($paginated);
    }

    public function show(EmployeeMap $employeeMap): JsonResponse
    {
        return response()->json($employeeMap->load('site'));
    }

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'fingerprint_pin' => ['required', 'string', 'max:20'],
            'fingerprint_nip' => ['required', 'string', 'max:20', 'unique:employee_maps,fingerprint_nip'],
            'nik' => ['nullable', 'string', 'max:20'],
            'hero_employee_uuid' => ['nullable', 'string', 'max:36'],
            'site_code' => ['nullable', 'string', 'max:10', 'exists:sites,code'],
            'active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $map = EmployeeMap::create($data);

        $audit->log('employee_map.create', 'EmployeeMap', $map->id, null, $data, null, $request->user());

        return response()->json($map, 201);
    }

    public function update(Request $request, EmployeeMap $employeeMap, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'fingerprint_pin' => ['sometimes', 'string', 'max:20'],
            'fingerprint_nip' => ['sometimes', 'string', 'max:20', 'unique:employee_maps,fingerprint_nip,'.$employeeMap->id],
            'nik' => ['nullable', 'string', 'max:20'],
            'hero_employee_uuid' => ['nullable', 'string', 'max:36'],
            'site_code' => ['nullable', 'string', 'max:10', 'exists:sites,code'],
            'active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $old = $employeeMap->only(array_keys($data));
        $employeeMap->update($data);

        $audit->log('employee_map.update', 'EmployeeMap', $employeeMap->id, $old, $data, null, $request->user());

        return response()->json($employeeMap);
    }

    public function destroy(Request $request, EmployeeMap $employeeMap, AuditLogService $audit): JsonResponse
    {
        $old = $employeeMap->toArray();
        $employeeMap->delete();

        $audit->log('employee_map.delete', 'EmployeeMap', $employeeMap->id, $old, null, null, $request->user());

        return response()->json(null, 204);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.fingerprint_pin' => ['required', 'string', 'max:20'],
            'mappings.*.fingerprint_nip' => ['required', 'string', 'max:20'],
            'mappings.*.nik' => ['nullable', 'string', 'max:20'],
            'mappings.*.site_code' => ['nullable', 'string', 'max:10'],
        ]);

        $created = [];
        foreach ($data['mappings'] as $item) {
            $created[] = EmployeeMap::updateOrCreate(
                ['fingerprint_nip' => $item['fingerprint_nip']],
                [
                    'fingerprint_pin' => $item['fingerprint_pin'],
                    'nik' => $item['nik'] ?? null,
                    'site_code' => $item['site_code'] ?? null,
                    'active' => true,
                ]
            );
        }

        return response()->json(['created' => count($created), 'mappings' => $created], 201);
    }

    public function unmatched(Request $request): JsonResponse
    {
        $query = FingerprintScan::whereNull('resolved_nik')
            ->select('raw_nip', 'raw_name', 'raw_pin', DB::raw('COUNT(*) as scan_count'))
            ->groupBy('raw_nip', 'raw_name', 'raw_pin');

        if ($request->filled('import_id')) {
            $query->where('import_id', $request->import_id);
        }

        return response()->json($query->orderBy('raw_nip')->get());
    }

    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'limit' => ['integer', 'min:1', 'max:20'],
        ]);

        $name = $request->name;
        $limit = $request->integer('limit', 10);

        $employees = HeroEmployeeCache::where('is_active', true)->get();

        $scored = $employees->map(function ($emp) use ($name) {
            $distance = levenshtein(
                strtolower(substr($name, 0, 255)),
                strtolower(substr($emp->fullname, 0, 255))
            );
            $soundexMatch = soundex($name) === soundex($emp->fullname) ? 0 : 10;

            return [
                'nik' => $emp->nik,
                'fullname' => $emp->fullname,
                'position' => $emp->position,
                'project_code' => $emp->project_code,
                'score' => $distance + $soundexMatch,
            ];
        })
            ->sortBy('score')
            ->take($limit)
            ->values();

        return response()->json($scored);
    }
}
