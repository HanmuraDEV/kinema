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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->unique();
            $table->string('title');
            $table->text('overview')->nullable();
            $table->string('genres')->nullable();
            $table->string('poster_path')->nullable();
            $table->date('release_date')->nullable();

            // Relación con el cluster de IA
            $table->foreignId('vibe_id')->nullable()->constrained('vibes')->nullOnDelete();

            // Motor de Machine Learning
            $table->float('x_coordinate')->nullable();
            $table->float('y_coordinate')->nullable();
            $table->vector('embedding', 768)->nullable();

            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
