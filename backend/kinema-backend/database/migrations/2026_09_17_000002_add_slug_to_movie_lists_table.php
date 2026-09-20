<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Slug por lista: permite URLs /{username}/lists/{slug} distintas por usuario
        Schema::table('movie_lists', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Rellenar slugs de listas existentes (únicos por usuario)
        $lists = DB::table('movie_lists')->select('id', 'user_id', 'name')->get();
        $used = [];
        foreach ($lists as $list) {
            $base = Str::slug($list->name ?: 'lista') ?: 'lista';
            $slug = $base;
            $n = 2;
            while (isset($used[$list->user_id . ':' . $slug])) {
                $slug = $base . '-' . $n++;
            }
            $used[$list->user_id . ':' . $slug] = true;
            DB::table('movie_lists')->where('id', $list->id)->update(['slug' => $slug]);
        }

        Schema::table('movie_lists', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_lists', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
