<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index composites pour le pilotage SLA et les filtres admin à l'échelle.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['status', 'first_contacted_at'], 'leads_status_first_contacted_idx');
            $table->index(['status', 'next_action_at'], 'leads_status_next_action_idx');
            $table->index(['assigned_to', 'status'], 'leads_assigned_status_idx');
            $table->index(['sector_id', 'status'], 'leads_sector_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_status_first_contacted_idx');
            $table->dropIndex('leads_status_next_action_idx');
            $table->dropIndex('leads_assigned_status_idx');
            $table->dropIndex('leads_sector_status_idx');
        });
    }
};
