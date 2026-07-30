<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_maps', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_pin', 20);
            $table->string('fingerprint_nip', 20)->unique();
            $table->string('nik', 20)->nullable()->index();
            $table->string('hero_employee_uuid', 36)->nullable();
            $table->string('site_code', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('site_code')->references('code')->on('sites')->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_maps');
    }
};
