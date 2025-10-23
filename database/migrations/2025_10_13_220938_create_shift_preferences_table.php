<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date'); // на какую дату пожелание
            $table->enum('type', [
                'day_off',
                'prefer_morning',
                'prefer_day',
                'prefer_night',
                'avoid_morning',
                'avoid_day',
                'avoid_night'
            ]);
            $table->timestamp('submitted_at'); // для приоритета!
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_preferences');
    }
};
