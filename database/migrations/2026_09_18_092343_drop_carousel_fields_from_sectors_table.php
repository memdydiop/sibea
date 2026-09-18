<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn(['hero_is_active', 'hero_sort_order', 'hero_scene']);
        });
    }

    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->boolean('hero_is_active')->default(true);
            $table->integer('hero_sort_order')->default(0);
            $table->string('hero_scene')->nullable();
        });
    }
};
