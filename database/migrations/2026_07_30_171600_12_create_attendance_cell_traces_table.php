<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_cell_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cell_id')->constrained('attendance_cells')->cascadeOnDelete();
            $table->string('rule_key', 100);
            $table->string('explanation', 255);
            $table->json('inputs');
            $table->timestamp('created_at')->useCurrent();

            $table->index('cell_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_cell_traces');
    }
};
