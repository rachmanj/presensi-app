<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrix_rules', function (Blueprint $table) {
            $table->id();
            $table->string('home_site_code', 10);
            $table->string('visit_site_code', 10);
            $table->string('code', 20);
            $table->integer('priority')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('home_site_code')->references('code')->on('sites')->cascadeOnUpdate();
            $table->foreign('visit_site_code')->references('code')->on('sites')->cascadeOnUpdate();
            $table->index(['home_site_code', 'visit_site_code', 'effective_from']);
            $table->unique(['home_site_code', 'visit_site_code', 'effective_from'], 'matrix_rules_home_visit_effective_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_rules');
    }
};
