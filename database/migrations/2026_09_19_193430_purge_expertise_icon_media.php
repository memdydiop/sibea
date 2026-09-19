<?php

use App\Models\Expertise;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Supprime les icônes d'expertise (champ retiré du back-office).
     */
    public function up(): void
    {
        Media::query()
            ->where('model_type', (new Expertise)->getMorphClass())
            ->where('collection_name', 'icon')
            ->get()
            ->each(fn (Media $media) => $media->delete());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
