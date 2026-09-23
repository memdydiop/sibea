<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lot 3 mini-CRM : référence lisible + prochaine action + montant estimé.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->unique()->after('id');
            $table->string('next_action', 255)->nullable()->after('notes');
            $table->timestamp('next_action_at')->nullable()->after('next_action')->index();
            $table->unsignedBigInteger('estimated_amount')->nullable()->after('next_action_at');
        });

        DB::table('leads')->orderBy('id')->chunkById(200, function ($leads) {
            foreach ($leads as $lead) {
                if ($lead->reference !== null) {
                    continue;
                }

                DB::table('leads')->where('id', $lead->id)->update([
                    'reference' => 'SIB-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'next_action', 'next_action_at', 'estimated_amount']);
        });
    }
};
