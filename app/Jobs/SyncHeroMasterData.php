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
            $normalized = $this->normalizeEmployee($employee);

            if ($normalized['nik'] === '') {
                continue;
            }

            if ($this->projectCode && $normalized['project_code'] !== $this->projectCode) {
                continue;
            }

            HeroEmployeeCache::updateOrCreate(
                ['nik' => $normalized['nik']],
                [
                    'hero_employee_uuid' => $normalized['hero_employee_uuid'],
                    'fullname' => $normalized['fullname'],
                    'position' => $normalized['position'],
                    'department' => $normalized['department'],
                    'project_code' => $normalized['project_code'],
                    'is_active' => $normalized['is_active'],
                    'synced_at' => now(),
                    'raw' => $employee,
                    'leave_balance' => null,
                ]
            );
            $synced++;
        }

        $this->suggestEmployeeMaps();

        Log::info("HERO sync completed: {$synced} employees cached");
    }

    /**
     * @param  array<string, mixed>  $employee
     * @return array{nik: string, hero_employee_uuid: ?string, fullname: string, position: ?string, department: ?string, project_code: ?string, is_active: bool}
     */
    private function normalizeEmployee(array $employee): array
    {
        $nik = $employee['nik'] ?? $employee['NIK'] ?? null;

        $fullname = is_array($employee['employee'] ?? null)
            ? ($employee['employee']['fullname'] ?? null)
            : null;
        $fullname = $fullname ?? $employee['fullname'] ?? $employee['name'] ?? '';

        $position = is_array($employee['position'] ?? null)
            ? ($employee['position']['position_name'] ?? null)
            : null;
        if ($position === null && isset($employee['position']) && is_string($employee['position'])) {
            $position = $employee['position'];
        }
        $position = $position ?? $employee['jabatan'] ?? null;

        $department = null;
        if (is_array($employee['position'] ?? null) && is_array($employee['position']['department'] ?? null)) {
            $department = $employee['position']['department']['department_name'] ?? null;
        }
        if ($department === null && isset($employee['department']) && is_string($employee['department'])) {
            $department = $employee['department'];
        }
        $department = $department ?? $employee['departemen'] ?? null;

        $projectCode = is_array($employee['project'] ?? null)
            ? ($employee['project']['project_code'] ?? null)
            : null;
        $projectCode = $projectCode ?? $employee['project_code'] ?? null;
        if ($projectCode === null && isset($employee['project']) && is_string($employee['project'])) {
            $projectCode = $employee['project'];
        }

        $heroEmployeeUuid = isset($employee['id'])
            ? (string) $employee['id']
            : ($employee['uuid'] ?? null);

        $isActive = (bool) ($employee['is_active'] ?? $employee['active'] ?? true);

        return [
            'nik' => $nik !== null ? (string) $nik : '',
            'hero_employee_uuid' => $heroEmployeeUuid,
            'fullname' => (string) $fullname,
            'position' => $position !== null ? (string) $position : null,
            'department' => $department !== null ? (string) $department : null,
            'project_code' => $projectCode !== null ? (string) $projectCode : null,
            'is_active' => $isActive,
        ];
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
