<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lot 1 mini-CRM : traçabilité d'origine + mesure SLA 24h ouvrées.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('expertise_id')->nullable()->after('sector_id')->constrained()->nullOnDelete();
            $table->string('origin_page', 500)->nullable()->after('source');
            $table->timestamp('first_contacted_at')->nullable()->after('notified_at')->index();
        });

        // Mapping anciens statuts génériques → pipeline commercial.
        DB::table('leads')->where('status', 'contacte')->update(['status' => 'qualification']);
        DB::table('leads')->where('status', 'en_cours')->update(['status' => 'qualification']);
        DB::table('leads')->where('status', 'qualifie')->update(['status' => 'qualification']);
        DB::table('leads')->where('status', 'converti')->update(['status' => 'gagne']);
        DB::table('leads')->where('status', 'non_qualifie')->update(['status' => 'perdu']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expertise_id');
            $table->dropColumn(['origin_page', 'first_contacted_at']);
        });
    }
};
