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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            $table->string('department'); // Ej: "Directing", "Acting"
            $table->string('job'); // Ej: "Director", "Actor", "Writer"
            $table->string('character')->nullable(); // Nombre del personaje si es actor
            $table->integer('order')->nullable(); // Orden de aparición en créditos

            $table->timestamps();

            // Previene que un mismo actor salga dos veces con el mismo rol en la misma película
            $table->unique(['movie_id', 'person_id', 'job']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
