<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Películas del historial Letterboxd: no traen tmdb_id (llega con TMDB)
        // ni fecha exacta (solo año). Ambos se rellenan al enriquecer.
        // (Sin doctrine/dbal: ALTER crudo en vez de ->change())
        DB::statement('ALTER TABLE movies ALTER COLUMN tmdb_id DROP NOT NULL');

        Schema::table('movies', function (Blueprint $table) {
            $table->smallInteger('release_year')->nullable()->after('release_date');
        });

        // Diario de Letterboxd: cuándo se vio + URI para dedupe
        Schema::table('reviews', function (Blueprint $table) {
            $table->date('watched_at')->nullable()->after('has_spoilers');
            $table->string('letterboxd_uri')->nullable()->after('watched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['watched_at', 'letterboxd_uri']);
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('release_year');
            // NOTA: revertir a NOT NULL falla si hay stubs sin tmdb_id
            // $table->unsignedBigInteger('tmdb_id')->nullable(false)->change();
        });
    }
};
