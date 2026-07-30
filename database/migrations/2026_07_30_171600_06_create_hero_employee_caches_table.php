<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_employee_caches', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->unique();
            $table->string('hero_employee_uuid', 36)->nullable()->index();
            $table->string('fullname', 150);
            $table->string('position', 150)->nullable();
            $table->string('department', 150)->nullable();
            $table->string('project_code', 10)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_employee_caches');
    }
};
