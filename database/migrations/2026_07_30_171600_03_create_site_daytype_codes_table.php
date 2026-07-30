<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_daytype_codes', function (Blueprint $table) {
            $table->id();
            $table->string('site_code', 10);
            $table->enum('day_type', ['workday', 'off', 'day6', 'day7_holiday', 'standby']);
            $table->string('shift', 20)->default('any');
            $table->string('code', 20);
            $table->timestamps();

            $table->foreign('site_code')->references('code')->on('sites')->cascadeOnUpdate();
            $table->unique(['site_code', 'day_type', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_daytype_codes');
    }
};
