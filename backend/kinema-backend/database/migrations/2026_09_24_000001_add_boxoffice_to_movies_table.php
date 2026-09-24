<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->unsignedBigInteger('budget')->nullable()->after('release_year');
            $table->unsignedBigInteger('revenue')->nullable()->after('budget');
            $table->unsignedInteger('runtime')->nullable()->after('revenue');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['budget', 'revenue', 'runtime']);
        });
    }
};
