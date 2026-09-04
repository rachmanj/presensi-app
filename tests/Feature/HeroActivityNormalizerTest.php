<?php

namespace Tests\Feature;

use App\Services\HeroActivityNormalizer;
use Tests\TestCase;

class HeroActivityNormalizerTest extends TestCase
{
    private HeroActivityNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = app(HeroActivityNormalizer::class);
    }

    public function test_approved_annual_leave_maps_code_1901(): void
    {
        $result = $this->normalizer->normalize([
            'success' => true,
            'leave_requests' => [
                [
                    'status' => 'approved',
                    'cancellations' => [],
                    'is_lsl_cashout_only' => false,
                    'start_date' => '2026-06-10',
                    'end_date' => '2026-06-12',
                    'leave_type' => [
                        'name' => 'Cuti Tahunan',
                        'category' => 'annual',
                    ],
                ],
            ],
            'official_travels' => [],
        ]);

        $this->assertCount(1, $result['leaves']);
        $this->assertSame('1901', $result['leaves'][0]['code']);
        $this->assertSame('Cuti Tahunan', $result['leaves'][0]['type_name']);
        $this->assertSame('2026-06-10', $result['leaves'][0]['start_date']);
        $this->assertSame('2026-06-12', $result['leaves'][0]['end_date']);
    }

    public function test_lsl_leave_maps_code_1901(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [
                [
                    'status' => 'approved',
                    'cancellations' => [],
                    'is_lsl_cashout_only' => false,
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-06-05',
                    'leave_type' => [
                        'name' => 'Long Service Leave',
                        'category' => 'lsl',
                    ],
                ],
            ],
            'official_travels' => [],
        ]);

        $this->assertCount(1, $result['leaves']);
        $this->assertSame('1901', $result['leaves'][0]['code']);
    }

    public function test_pending_leave_is_skipped(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [
                [
                    'status' => 'pending',
                    'cancellations' => [],
                    'is_lsl_cashout_only' => false,
                    'start_date' => '2026-06-10',
                    'end_date' => '2026-06-12',
                    'leave_type' => [
                        'name' => 'Cuti Tahunan',
                        'category' => 'annual',
                    ],
                ],
            ],
            'official_travels' => [],
        ]);

        $this->assertSame([], $result['leaves']);
    }

    public function test_canceled_leave_is_skipped(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [
                [
                    'status' => 'approved',
                    'cancellations' => [
                        ['id' => 1, 'status' => 'approved'],
                    ],
                    'is_lsl_cashout_only' => false,
                    'start_date' => '2026-06-10',
                    'end_date' => '2026-06-12',
                    'leave_type' => [
                        'name' => 'Cuti Tahunan',
                        'category' => 'annual',
                    ],
                ],
            ],
            'official_travels' => [],
        ]);

        $this->assertSame([], $result['leaves']);
    }

    public function test_lsl_cashout_only_leave_is_skipped(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [
                [
                    'status' => 'approved',
                    'cancellations' => [],
                    'is_lsl_cashout_only' => true,
                    'start_date' => '2026-06-10',
                    'end_date' => '2026-06-12',
                    'leave_type' => [
                        'name' => 'LSL Cashout',
                        'category' => 'lsl',
                    ],
                ],
            ],
            'official_travels' => [],
        ]);

        $this->assertSame([], $result['leaves']);
    }

    public function test_travel_date_timezone_and_duration_end_date(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [],
            'official_travels' => [
                [
                    'status' => 'approved',
                    'official_travel_date' => '2026-08-05T16:00:00.000000Z',
                    'duration' => '3 Days',
                    'destination' => '001H - BO - Jakarta',
                ],
            ],
        ]);

        $this->assertCount(1, $result['lots']);
        $this->assertSame('2026-08-06', $result['lots'][0]['start_date']);
        $this->assertSame('2026-08-08', $result['lots'][0]['end_date']);
    }

    public function test_destination_parses_site_code_bo(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [],
            'official_travels' => [
                [
                    'status' => 'approved',
                    'official_travel_date' => '2026-08-05T16:00:00.000000Z',
                    'duration' => '1 Day',
                    'destination' => '001H - BO - Jakarta',
                ],
            ],
        ]);

        $this->assertSame('BO', $result['lots'][0]['site_code']);
    }

    public function test_no_destination_match_returns_null_site(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [],
            'official_travels' => [
                [
                    'status' => 'approved',
                    'official_travel_date' => '2026-08-05T16:00:00.000000Z',
                    'duration' => '1 Day',
                    'destination' => '001H - Jakarta',
                    'traveler' => [
                        'project' => [
                            'project_code' => 'HO',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertNull($result['lots'][0]['site_code']);
    }

    public function test_already_canonical_input_is_returned_unchanged(): void
    {
        $canonical = [
            'leaves' => [
                ['start_date' => '2026-06-01', 'end_date' => '2026-06-01', 'code' => '1901'],
            ],
            'lots' => [
                ['start_date' => '2026-06-05', 'end_date' => '2026-06-07', 'site_code' => '017C'],
            ],
            'overtimes' => [],
        ];

        $result = $this->normalizer->normalize($canonical);

        $this->assertSame($canonical, $result);
    }

    public function test_overtime_requests_are_passthrough_as_overtimes(): void
    {
        $overtimeRequests = [
            ['date' => '2026-06-15', 'hours' => 4],
        ];

        $result = $this->normalizer->normalize([
            'leave_requests' => [],
            'official_travels' => [],
            'overtime_requests' => $overtimeRequests,
        ]);

        $this->assertSame($overtimeRequests, $result['overtimes']);
    }

    public function test_missing_overtime_requests_yields_empty_overtimes(): void
    {
        $result = $this->normalizer->normalize([
            'leave_requests' => [],
            'official_travels' => [],
        ]);

        $this->assertSame([], $result['overtimes']);
    }
}
