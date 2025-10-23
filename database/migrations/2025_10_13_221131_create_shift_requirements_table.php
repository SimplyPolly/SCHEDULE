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
            $table->enum('day_type', ['weekday', 'weekend', 'holiday']);
            $table->enum('shift_type', ['morning', 'day', 'night']);
            $table->enum('role', ['cook', 'waiter', 'hostess', 'bartender', 'admin']);
            $table->integer('min_staff');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_requirements');
    }
};
