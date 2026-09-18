<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (DB::table('projects')->get(['id', 'status']) as $project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['status' => $this->normalize($project->status)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $labels = [
            'livre' => 'Livré',
            'en_cours' => 'En cours',
            'en_developpement' => 'En développement',
        ];

        foreach (DB::table('projects')->get(['id', 'status']) as $project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['status' => $labels[$project->status] ?? $project->status]);
        }
    }

    private function normalize(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $key = Str::of($status)->ascii()->lower()->trim()->replace([' ', '-'], '_')->toString();

        return match ($key) {
            'livre', 'termine' => 'livre',
            'en_cours' => 'en_cours',
            'en_developpement' => 'en_developpement',
            default => null,
        };
    }
};
