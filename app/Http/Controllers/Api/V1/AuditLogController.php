<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        return response()->json($query->paginate($request->integer('per_page', 25)));
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json($auditLog->load('user'));
    }
}
