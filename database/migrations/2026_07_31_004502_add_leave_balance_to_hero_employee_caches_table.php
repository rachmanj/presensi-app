<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_employee_caches', function (Blueprint $table) {
            $table->json('leave_balance')->nullable()->after('raw');
        });
    }

    public function down(): void
    {
        Schema::table('hero_employee_caches', function (Blueprint $table) {
            $table->dropColumn('leave_balance');
        });
    }
};
