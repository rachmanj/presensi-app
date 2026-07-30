<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained('attendance_rows')->cascadeOnDelete();
            $table->date('work_date');
            $table->tinyInteger('day_of_month');
            $table->enum('day_type', ['workday', 'saturday', 'sunday', 'holiday', 'day6', 'day7']);
            $table->string('auto_code', 20)->nullable();
            $table->string('final_code', 20)->nullable();
            $table->boolean('is_overridden')->default(false);
            $table->string('override_by', 100)->nullable();
            $table->string('override_reason', 255)->nullable();
            $table->string('visit_site_code', 10)->nullable();
            $table->timestamps();

            $table->unique(['row_id', 'work_date']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_cells');
    }
};
