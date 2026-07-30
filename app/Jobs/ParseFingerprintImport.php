<?php

namespace App\Jobs;

use App\Models\FingerprintImport;
use App\Services\FingerprintParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParseFingerprintImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public FingerprintImport $import)
    {
        $this->onQueue('imports');
    }

    public function handle(FingerprintParser $parser): void
    {
        $this->import->update(['status' => 'parsing']);
        $this->import->scans()->delete();

        try {
            $path = Storage::disk('local')->path($this->import->stored_path);

            if ($this->import->format === FingerprintParser::FORMAT_SCANLOG) {
                $parser->parseFormat1($path, $this->import);
            } else {
                $parser->parseFormat2($path, $this->import);
            }

            $this->import->update(['status' => 'parsed']);
        } catch (\Throwable $e) {
            Log::error('Fingerprint parse failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->import->update([
                'status' => 'failed',
                'parse_errors' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            throw $e;
        }
    }
}
