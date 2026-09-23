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
        // Tabla sin uso: el heatmap se calcula directo de reviews
        // (watched_at/created_at). Decisión de diseño Fase D.
        Schema::dropIfExists('activity_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->nullableMorphs('subject');
            $table->timestamps();
        });
    }
};
