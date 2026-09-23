<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suite mini-CRM : qualification B2B/Particulier + échéance projet.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('prospect_type', 20)->nullable()->after('company')->index();
            $table->date('deadline')->nullable()->after('estimated_amount')->index();
        });

        // Backfill : avec société → Entreprise, sinon Particulier.
        DB::table('leads')->whereNull('prospect_type')->whereNotNull('company')->where('company', '<>', '')->update(['prospect_type' => 'entreprise']);
        DB::table('leads')->whereNull('prospect_type')->update(['prospect_type' => 'particulier']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['prospect_type', 'deadline']);
        });
    }
};
