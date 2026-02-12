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
        Schema::create('shift_requirements', function (Blueprint $table) {
            $table->id();
            $table->enum('season', ['season', 'offseason'])->default('season');
            $table->enum('day_type', ['weekday', 'weekend', 'holiday']);
            $table->enum('shift_type', ['morning', 'day', 'night']);
            $table->enum('role', ['cook', 'waiter', 'hostess', 'bartender', 'admin']);
            $table->unsignedInteger('min_staff');
            $table->timestamps();
            
            // Уникальный индекс: комбинация сезона, типа дня, типа смены и роли
            $table->unique(['season', 'day_type', 'shift_type', 'role']);
            
            // Индексы для оптимизации
            $table->index(['season', 'day_type']);
            $table->index(['season', 'shift_type']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_requirements');
    }
};