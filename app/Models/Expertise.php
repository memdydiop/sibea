<?php

namespace App\Models;

use Database\Factories\ExpertiseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $description
 * @property array<int, mixed>|null $benefits
 * @property array<int, mixed>|null $process_steps
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'short_description', 'description', 'benefits', 'process_steps', 'is_active', 'sort_order'])]
class Expertise extends Model implements HasMedia
{
    /** @use HasFactory<ExpertiseFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'process_steps' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Expertise>  $query
     * @return Builder<Expertise>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Expertise>  $query
     * @return Builder<Expertise>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Sector, $this>
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_expertise');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->nonQueued()->fit(Fit::Crop, 600, 400)->format('webp');
        $this->addMediaConversion('medium')->nonQueued()->fit(Fit::Crop, 1200, 800)->format('webp');
        $this->addMediaConversion('og')->nonQueued()->fit(Fit::Crop, 1200, 630)->format('webp');
    }
}
