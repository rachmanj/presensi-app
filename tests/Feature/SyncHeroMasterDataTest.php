<?php

namespace Tests\Feature;

use App\Jobs\SyncHeroMasterData;
use App\Models\HeroEmployeeCache;
use App\Services\HeroApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncHeroMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.hero.base_url' => 'http://hero.test',
            'services.hero.api_key' => 'test-key',
        ]);

        Cache::flush();
    }

    public function test_sync_maps_nested_hero_employee_payload(): void
    {
        Http::fake([
            'http://hero.test/api/employees' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 12345,
                        'nik' => '10022',
                        'is_active' => 1,
                        'employee' => ['fullname' => 'John Doe'],
                        'position' => [
                            'id' => 1,
                            'position_name' => 'Staff IT',
                            'department' => [
                                'id' => 10,
                                'department_name' => 'Information Technology',
                            ],
                        ],
                        'project' => [
                            'id' => 5,
                            'project_code' => '017C',
                            'project_name' => 'Project Alpha',
                        ],
                    ],
                    [
                        'id' => 67890,
                        'nik' => '10023',
                        'is_active' => 1,
                        'employee' => ['fullname' => 'Other Site'],
                        'position' => [
                            'id' => 2,
                            'position_name' => 'Operator',
                            'department' => [
                                'id' => 11,
                                'department_name' => 'Operations',
                            ],
                        ],
                        'project' => [
                            'id' => 6,
                            'project_code' => 'APS',
                            'project_name' => 'APS Site',
                        ],
                    ],
                ],
            ]),
        ]);

        (new SyncHeroMasterData('017C'))->handle(app(HeroApiClient::class));

        $this->assertDatabaseCount('hero_employee_caches', 1);

        $cache = HeroEmployeeCache::where('nik', '10022')->first();

        $this->assertNotNull($cache);
        $this->assertSame('12345', $cache->hero_employee_uuid);
        $this->assertSame('John Doe', $cache->fullname);
        $this->assertSame('Staff IT', $cache->position);
        $this->assertSame('Information Technology', $cache->department);
        $this->assertSame('017C', $cache->project_code);
        $this->assertTrue($cache->is_active);
        $this->assertNull($cache->leave_balance);

        Http::assertSentCount(1);
    }

    public function test_sync_maps_flat_legacy_hero_employee_payload(): void
    {
        Http::fake([
            'http://hero.test/api/employees' => Http::response([
                'data' => [
                    [
                        'id' => 99,
                        'uuid' => 'legacy-uuid',
                        'nik' => '10750',
                        'is_active' => 0,
                        'name' => 'Jane Smith',
                        'jabatan' => 'Supervisor',
                        'departemen' => 'Human Resources',
                        'project' => 'APS',
                    ],
                ],
            ]),
        ]);

        (new SyncHeroMasterData)->handle(app(HeroApiClient::class));

        $cache = HeroEmployeeCache::where('nik', '10750')->first();

        $this->assertNotNull($cache);
        $this->assertSame('99', $cache->hero_employee_uuid);
        $this->assertSame('Jane Smith', $cache->fullname);
        $this->assertSame('Supervisor', $cache->position);
        $this->assertSame('Human Resources', $cache->department);
        $this->assertSame('APS', $cache->project_code);
        $this->assertFalse($cache->is_active);
        $this->assertNull($cache->leave_balance);

        Http::assertSentCount(1);
    }
}
