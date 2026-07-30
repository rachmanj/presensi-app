<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingerprint_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('fingerprint_imports')->cascadeOnDelete();
            $table->string('raw_pin', 20);
            $table->string('raw_nip', 20);
            $table->string('raw_name', 150)->nullable();
            $table->date('scan_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('manual_code', 20)->nullable();
            $table->string('source_sheet', 20)->nullable();
            $table->json('extra')->nullable();
            $table->string('resolved_nik', 20)->nullable()->index();
            $table->timestamps();

            $table->index(['import_id', 'scan_date']);
            $table->index('raw_nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_scans');
    }
};
