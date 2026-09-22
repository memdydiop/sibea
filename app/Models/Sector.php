<?php

namespace App\Models;

use App\Support\SitemapCache;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
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
 * @property string|null $hero_title
 * @property string|null $hero_description
 * @property string|null $hero_cta_label
 * @property string|null $page_intro_title
 * @property string|null $page_intro_text
 * @property array<int, array<string, string>>|null $page_cards
 * @property array<int, array<string, string>>|null $page_figures
 * @property string|null $page_cta_title
 * @property string|null $page_cta_text
 * @property string|null $page_cta_label
 * @property bool $is_active
 * @property bool $is_locked
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'short_description', 'description', 'hero_title', 'hero_description', 'hero_cta_label', 'page_intro_title', 'page_intro_text', 'page_cards', 'page_figures', 'page_cta_title', 'page_cta_text', 'page_cta_label', 'is_active', 'is_locked', 'sort_order'])]
class Sector extends Model implements HasMedia
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::flushActiveListCache();
            SitemapCache::forget();
        });
        static::deleted(function (): void {
            self::flushActiveListCache();
            SitemapCache::forget();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_cards' => 'array',
            'page_figures' => 'array',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Sector>  $query
     * @return Builder<Sector>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Sector>  $query
     * @return Builder<Sector>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Expertise, $this>
     */
    public function expertises(): BelongsToMany
    {
        return $this->belongsToMany(Expertise::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('icon')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->nonQueued()->fit(Fit::Crop, 600, 400)->format('webp');
        $this->addMediaConversion('medium')->nonQueued()->fit(Fit::Crop, 1200, 800)->format('webp');
        $this->addMediaConversion('og')->nonQueued()->fit(Fit::Crop, 1200, 630)->format('webp');
    }

    /**
     * Liste publique mise en cache (1 h), invalidée à chaque mutation admin.
     *
     * Ne met en cache que des tableaux (jamais de modèles hydratés) pour
     * rester insensible aux problèmes de désérialisation.
     *
     * @return Collection<int, static>
     */
    public static function cachedActiveList(): Collection
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = Cache::remember(
            'sectors.active_ordered.v1',
            3600,
            fn (): array => self::query()->active()->ordered()->get()->toArray()
        );

        /** @var Collection<int, static> */
        return self::hydrate($items);
    }

    public static function flushActiveListCache(): void
    {
        Cache::forget('sectors.active_ordered');
        Cache::forget('sectors.active_ordered.v1');
    }
}
