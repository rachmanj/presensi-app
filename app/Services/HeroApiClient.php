<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HeroApiClient
{
    private const CIRCUIT_KEY = 'hero:circuit:open';

    private const FAILURE_KEY = 'hero:circuit:failures';

    private const MAX_FAILURES = 3;

    private const CIRCUIT_TTL = 60;

    public function getEmployees(): array
    {
        return $this->cachedRequest('GET', '/api/employees', [], 'hero:employees', 21600);
    }

    public function getActiveEmployees(): array
    {
        return $this->cachedRequest('GET', '/api/employees/active', [], 'hero:employees:active', 21600);
    }

    public function getEmployeeByNik(string $nik): ?array
    {
        $result = $this->cachedRequest(
            'GET',
            "/api/employees/by-nik/{$nik}",
            [],
            "hero:employee:{$nik}",
            21600
        );

        return $result ?: null;
    }

    public function getProjects(): array
    {
        return $this->cachedRequest('GET', '/api/projects', [], 'hero:projects', 21600);
    }

    public function getActivity(string $nik, int $year, ?int $month = null): array
    {
        $query = ['year' => $year];
        if ($month !== null) {
            $query['month'] = $month;
        }

        return $this->cachedRequest(
            'GET',
            "/api/workforce/employees/by-nik/{$nik}/activity",
            $query,
            $this->cacheKey("/api/workforce/employees/by-nik/{$nik}/activity", $query),
            1800
        );
    }

    private function cachedRequest(string $method, string $path, array $query, string $cacheKey, int $ttl): array
    {
        if ($this->isCircuitOpen()) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            Log::warning('HERO circuit open, no cache available', ['path' => $path]);

            return [];
        }

        return Cache::remember($cacheKey, $ttl, function () use ($method, $path, $query) {
            $normalized = $this->request($method, $path, $query);

            return $normalized['data'] ?? [];
        });
    }

    private function request(string $method, string $path, array $query = []): array
    {
        $baseUrl = rtrim(config('services.hero.base_url'), '/');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Key' => config('services.hero.api_key'),
                    'Accept' => 'application/json',
                ])
                ->send($method, "{$baseUrl}{$path}", ['query' => $query]);

            if ($response->successful()) {
                Cache::forget(self::FAILURE_KEY);

                return $this->normalizeResponse($response->json() ?? []);
            }

            $this->recordFailure();
            Log::error('HERO API error', ['path' => $path, 'status' => $response->status()]);

            return ['ok' => false, 'data' => []];
        } catch (\Throwable $e) {
            $this->recordFailure();
            Log::error('HERO API exception', ['path' => $path, 'message' => $e->getMessage()]);

            return ['ok' => false, 'data' => []];
        }
    }

    private function normalizeResponse(array $raw): array
    {
        if (isset($raw['status']) && $raw['status'] === 'success') {
            return ['ok' => true, 'data' => $raw['data'] ?? $raw];
        }

        if (isset($raw['success']) && $raw['success'] === true) {
            return ['ok' => true, 'data' => $raw['data'] ?? $raw];
        }

        if (isset($raw['data'])) {
            return ['ok' => true, 'data' => $raw['data']];
        }

        return ['ok' => true, 'data' => $raw];
    }

    private function cacheKey(string $path, array $query): string
    {
        ksort($query);

        return 'hero:'.md5($path.json_encode($query));
    }

    private function isCircuitOpen(): bool
    {
        return Cache::has(self::CIRCUIT_KEY);
    }

    private function recordFailure(): void
    {
        $failures = (int) Cache::get(self::FAILURE_KEY, 0) + 1;
        Cache::put(self::FAILURE_KEY, $failures, self::CIRCUIT_TTL);

        if ($failures >= self::MAX_FAILURES) {
            Cache::put(self::CIRCUIT_KEY, true, self::CIRCUIT_TTL);
        }
    }
}
