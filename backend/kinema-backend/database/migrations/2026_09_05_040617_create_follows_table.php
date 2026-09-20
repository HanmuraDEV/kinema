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
            Schema::create('follows', function (Blueprint $table) {
            $table->id();

            // Quién sigue
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();

            // A quién sigue
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // Bloqueo estricto: Una persona solo puede seguir a otra una única vez
            $table->unique(['follower_id', 'followed_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
