<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('attendance_sheets')->cascadeOnDelete();
            $table->string('nik', 20);
            $table->string('employee_name', 150);
            $table->string('position', 150)->nullable();
            $table->string('home_site_code', 10)->nullable();
            $table->integer('working_days')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->unique(['sheet_id', 'nik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rows');
    }
};
