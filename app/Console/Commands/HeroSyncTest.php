<?php

namespace App\Console\Commands;

use App\Jobs\SyncHeroMasterData;
use App\Models\HeroEmployeeCache;
use Illuminate\Console\Command;

class HeroSyncTest extends Command
{
    protected $signature = 'hero:sync-test {--project= : Filter by project code}';

    protected $description = 'Trigger HERO master data sync and print results';

    public function handle(): int
    {
        $this->info('Dispatching SyncHeroMasterData job...');

        SyncHeroMasterData::dispatchSync($this->option('project'));

        $count = HeroEmployeeCache::count();
        $this->info("Sync complete. hero_employee_caches row count: {$count}");

        $sample = HeroEmployeeCache::latest('synced_at')->limit(5)->get(['nik', 'fullname', 'project_code']);
        if ($sample->isNotEmpty()) {
            $this->table(['NIK', 'Fullname', 'Project'], $sample->map(fn ($r) => [
                $r->nik, $r->fullname, $r->project_code,
            ])->toArray());
        }

        return self::SUCCESS;
    }
}
