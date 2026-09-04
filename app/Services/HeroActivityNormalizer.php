<?php

namespace App\Services;

use Carbon\Carbon;

class HeroActivityNormalizer
{
    private const SITE_CODES = ['017C', '021C', '022C', '023C', '025C', 'APS', 'HO', 'BO'];

    private const UNPAID_TOKENS = ['tanpa upah', 'unpaid', 'not paid'];

    public function normalize(array $activity): array
    {
        if (isset($activity['leaves']) && ! isset($activity['leave_requests'])) {
            return $activity;
        }

        $result = $activity;
        $result['leaves'] = $this->normalizeLeaves($activity['leave_requests'] ?? []);
        $result['lots'] = $this->normalizeLots($activity['official_travels'] ?? []);
        $result['overtimes'] = isset($activity['overtime_requests'])
            ? $activity['overtime_requests']
            : [];

        unset($result['leave_requests'], $result['official_travels'], $result['overtime_requests']);

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $leaveRequests
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLeaves(array $leaveRequests): array
    {
        $leaves = [];

        foreach ($leaveRequests as $request) {
            if (! $this->isApprovedLeave($request)) {
                continue;
            }

            $leaveType = $request['leave_type'] ?? [];
            $typeName = $leaveType['name'] ?? $leaveType['type_name'] ?? '';

            $leaves[] = [
                'start_date' => $request['start_date'] ?? null,
                'end_date' => $request['end_date'] ?? $request['start_date'] ?? null,
                'code' => $this->mapLeaveCode($leaveType),
                'type_name' => $typeName,
            ];
        }

        return $leaves;
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private function isApprovedLeave(array $request): bool
    {
        $status = strtolower((string) ($request['status'] ?? ''));
        if (! in_array($status, ['approved', 'closed'], true)) {
            return false;
        }

        if (! empty($request['cancellations'])) {
            return false;
        }

        if (! empty($request['is_lsl_cashout_only'])) {
            return false;
        }

        return ($request['start_date'] ?? null) !== null;
    }

    /**
     * @param  array<string, mixed>  $leaveType
     */
    private function mapLeaveCode(array $leaveType): string
    {
        $name = strtolower((string) ($leaveType['name'] ?? $leaveType['type_name'] ?? ''));
        $category = strtolower((string) ($leaveType['category'] ?? ''));
        $isPaid = ! $this->isUnpaid($name, $category);

        if ($category === 'annual' || $category === 'lsl' || str_contains($name, 'cuti')) {
            return '1901';
        }

        if (str_contains($name, 'sakit')) {
            return $isPaid ? '1904' : '1905';
        }

        if (str_contains($name, 'izin')) {
            return $isPaid ? '1902' : '1903';
        }

        return '1901';
    }

    private function isUnpaid(string $name, string $category): bool
    {
        $haystack = $name.' '.$category;

        foreach (self::UNPAID_TOKENS as $token) {
            if (str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $officialTravels
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLots(array $officialTravels): array
    {
        $lots = [];

        foreach ($officialTravels as $travel) {
            if (strtolower((string) ($travel['status'] ?? '')) !== 'approved') {
                continue;
            }

            $start = $this->parseTravelStartDate($travel);
            if ($start === null) {
                continue;
            }

            $days = $this->parseDurationDays($travel['duration'] ?? '1 Day');
            $end = $start->copy()->addDays(max(0, $days - 1));

            $lots[] = [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'site_code' => $this->parseSiteFromDestination(
                    $travel['destination'] ?? null,
                ),
            ];
        }

        return $lots;
    }

    /**
     * @param  array<string, mixed>  $travel
     */
    private function parseTravelStartDate(array $travel): ?Carbon
    {
        $raw = $travel['official_travel_date'] ?? $travel['departure_from'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        return Carbon::parse($raw)->timezone('Asia/Makassar')->startOfDay();
    }

    private function parseDurationDays(string $duration): int
    {
        if (preg_match('/(\d+)\s*day/i', $duration, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    private function parseSiteFromDestination(?string $destination): ?string
    {
        if ($destination === null || $destination === '') {
            return null;
        }

        $matches = [];
        foreach (self::SITE_CODES as $code) {
            if (preg_match('/\b'.preg_quote($code, '/').'\b/', $destination)) {
                $matches[] = $code;
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        return $matches[0];
    }
}
