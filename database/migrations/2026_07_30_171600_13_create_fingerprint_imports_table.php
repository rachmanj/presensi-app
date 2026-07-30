<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingerprint_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('attendance_periods');
            $table->string('site_code', 10);
            $table->enum('format', ['format1_scanlog', 'format2_paired']);
            $table->string('original_filename', 255);
            $table->string('stored_path', 255);
            $table->enum('status', ['uploaded', 'parsing', 'parsed', 'failed'])->default('uploaded');
            $table->integer('rows_total')->default(0);
            $table->integer('rows_matched')->default(0);
            $table->integer('rows_unmatched')->default(0);
            $table->json('parse_errors')->nullable();
            $table->string('uploaded_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_imports');
    }
};
