<?php

namespace App\Jobs;

use App\Models\EmployeeMap;
use App\Models\HeroEmployeeCache;
use App\Services\HeroApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncHeroMasterData implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public ?string $projectCode = null)
    {
        $this->onQueue('sync');
    }

    public function handle(HeroApiClient $client): void
    {
        $employees = $client->getEmployees();
        $synced = 0;

        foreach ($employees as $employee) {
            $nik = $employee['nik'] ?? $employee['NIK'] ?? null;
            if (! $nik) {
                continue;
            }

            if ($this->projectCode && ($employee['project_code'] ?? $employee['project'] ?? null) !== $this->projectCode) {
                continue;
            }

            HeroEmployeeCache::updateOrCreate(
                ['nik' => (string) $nik],
                [
                    'hero_employee_uuid' => $employee['uuid'] ?? $employee['id'] ?? null,
                    'fullname' => $employee['fullname'] ?? $employee['name'] ?? '',
                    'position' => $employee['position'] ?? $employee['jabatan'] ?? null,
                    'department' => $employee['department'] ?? $employee['departemen'] ?? null,
                    'project_code' => $employee['project_code'] ?? $employee['project'] ?? null,
                    'is_active' => $employee['is_active'] ?? $employee['active'] ?? true,
                    'synced_at' => now(),
                    'raw' => $employee,
                ]
            );
            $synced++;
        }

        $this->suggestEmployeeMaps();

        Log::info("HERO sync completed: {$synced} employees cached");
    }

    private function suggestEmployeeMaps(): void
    {
        $caches = HeroEmployeeCache::where('is_active', true)->get();

        foreach ($caches as $cache) {
            $existing = EmployeeMap::where('nik', $cache->nik)->exists();
            if ($existing) {
                continue;
            }

            $similar = EmployeeMap::whereNull('nik')
                ->where('active', true)
                ->get()
                ->first(fn ($map) => $this->namesAreSimilar($map->note ?? '', $cache->fullname));

            if ($similar) {
                $similar->update([
                    'nik' => $cache->nik,
                    'hero_employee_uuid' => $cache->hero_employee_uuid,
                    'site_code' => $cache->project_code,
                ]);
            }
        }
    }

    private function namesAreSimilar(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        similar_text(strtolower($a), strtolower($b), $percent);

        return $percent >= 80;
    }
}
