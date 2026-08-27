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
        // Migración: create_movie_list_items_table
        Schema::create('movie_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_list_id')->constrained('movie_lists')->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['movie_list_id', 'movie_id']); // Una película solo puede estar una vez por lista
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_list_items');
    }
};
