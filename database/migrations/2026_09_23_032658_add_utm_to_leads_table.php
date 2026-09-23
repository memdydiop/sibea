<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traçabilité campagne : UTM capturés depuis l'URL + session.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('utm_source', 255)->nullable()->after('origin_page');
            $table->string('utm_medium', 255)->nullable()->after('utm_source');
            $table->string('utm_campaign', 255)->nullable()->after('utm_medium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign']);
        });
    }
};
