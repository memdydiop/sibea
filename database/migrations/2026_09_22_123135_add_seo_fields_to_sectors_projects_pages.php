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
        foreach (['sectors', 'projects', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('meta_title', 255)->nullable()->after('slug');
                $table->string('meta_description', 500)->nullable()->after('meta_title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['sectors', 'projects', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
