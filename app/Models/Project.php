<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
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
 * @property string $title
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $challenge
 * @property string|null $solution
 * @property string|null $impact
 * @property string|null $location
 * @property string|null $client_name
 * @property ProjectStatus|null $status
 * @property string|null $duration
 * @property string|null $surface
 * @property string|null $budget
 * @property string|null $testimonial_quote
 * @property string|null $testimonial_author
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $is_active
 * @property bool $is_published
 * @property array<int, mixed>|null $results
 */
#[Fillable(['title', 'slug', 'short_description', 'description', 'challenge', 'solution', 'impact', 'location', 'project_date', 'status', 'duration', 'surface', 'budget', 'client_name', 'client_publishable', 'testimonial_quote', 'testimonial_author', 'latitude', 'longitude', 'results', 'is_active', 'is_published'])]
class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project_date' => 'date',
            'status' => ProjectStatus::class,
            'client_publishable' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'results' => 'array',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @return BelongsToMany<Sector, $this>
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    /**
     * @return BelongsToMany<Expertise, $this>
     */
    public function expertises(): BelongsToMany
    {
        return $this->belongsToMany(Expertise::class, 'project_expertise');
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'project_service');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('og_image')->singleFile();
        $this->addMediaCollection('documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('cover', 'gallery', 'og_image')->fit(Fit::Crop, 600, 400)->format('webp');
        $this->addMediaConversion('medium')->performOnCollections('cover', 'gallery', 'og_image')->fit(Fit::Crop, 1200, 800)->format('webp');
        $this->addMediaConversion('og')->performOnCollections('cover', 'gallery', 'og_image')->fit(Fit::Crop, 1200, 630)->format('webp');
    }
}
