<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFingerprintWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FingerprintWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $apiKey = $request->header('X-API-Key') ?? $request->header('X-Webhook-Key');
        $expected = config('services.fingerprint.webhook_key');

        if (! $expected || $apiKey !== $expected) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        $payload = $request->validate([
            'pin' => ['required', 'string'],
            'timestamp' => ['required', 'string'],
            'direction' => ['required', 'string', 'in:in,out,1,2'],
            'machine_sn' => ['nullable', 'string'],
            'raw_name' => ['nullable', 'string'],
        ]);

        ProcessFingerprintWebhook::dispatch($payload);

        return response()->json(['message' => 'Accepted', 'status' => 'queued'], 202);
    }
}
