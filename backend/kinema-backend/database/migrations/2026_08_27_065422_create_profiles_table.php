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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();

            // Las famosas 4 películas favoritas (Top 4)
            $table->foreignId('top_movie_1')->nullable()->constrained('movies')->nullOnDelete();
            $table->foreignId('top_movie_2')->nullable()->constrained('movies')->nullOnDelete();
            $table->foreignId('top_movie_3')->nullable()->constrained('movies')->nullOnDelete();
            $table->foreignId('top_movie_4')->nullable()->constrained('movies')->nullOnDelete();

            $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
