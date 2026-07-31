<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFingerprintWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload)
    {
        $this->onQueue('fingerprint');
    }

    public function handle(): void
    {
        // Stub: full implementation requires actual machine API format from vendor
        Log::info('Fingerprint webhook received (stub)', $this->payload);
    }
}
