<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('attendance_periods')->cascadeOnDelete();
            $table->string('site_code', 10);
            $table->foreignId('report_template_id')->nullable()->constrained('report_templates');
            $table->enum('status', ['draft', 'processing', 'review', 'finalized'])->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('site_code')->references('code')->on('sites')->cascadeOnUpdate();
            $table->unique(['period_id', 'site_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sheets');
    }
};
