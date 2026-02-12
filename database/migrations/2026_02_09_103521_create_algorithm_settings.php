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
        Schema::create('algorithm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('boolean'); 
            $table->text('description')->nullable();
            $table->string('category')->default('general'); 
            $table->timestamps();
            
            // Дополнительные индексы для поиска
            $table->index('category');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('algorithm_settings');
    }
};