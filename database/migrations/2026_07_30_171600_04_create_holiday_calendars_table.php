<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->enum('type', ['national_holiday', 'joint_leave', 'special']);
            $table->string('description', 255)->nullable();
            $table->smallInteger('year');
            $table->timestamps();

            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_calendars');
    }
};
