<?php

namespace App\Services;

use App\Exceptions\MatrixRuleNotFoundException;
use App\Models\MatrixRule;
use App\Models\Site;
use Carbon\Carbon;

class MatrixResolver
{
    public function resolve(string $homeSite, ?string $visitSite, Carbon $date): array
    {
        $visitSite = $visitSite ?: $homeSite;

        $rule = MatrixRule::where('home_site_code', $homeSite)
            ->where('visit_site_code', $visitSite)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('priority')
            ->first();

        if ($rule) {
            return ['code' => $rule->code, 'rule' => $rule];
        }

        if ($visitSite === $homeSite) {
            $site = Site::where('code', $homeSite)->first();
            if ($site) {
                return [
                    'code' => $site->base_present_code,
                    'rule' => null,
                ];
            }
        }

        throw new MatrixRuleNotFoundException($homeSite, $visitSite);
    }
}
