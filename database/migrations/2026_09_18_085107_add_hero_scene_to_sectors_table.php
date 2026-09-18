<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->string('hero_scene')->nullable()->after('hero_sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn('hero_scene');
        });
    }
};
