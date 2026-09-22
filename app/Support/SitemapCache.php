<?php

namespace App\Support;

use App\Models\Expertise;
use App\Models\Page;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Setting;
use App\Models\Statistic;
use App\Models\Testimonial;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Throwable;

class SitemapCache
{
    public const KEY = 'sitemap.xml.v1';

    public const TTL = 3600;

    /**
     * Render the sitemap, served from cache when the store is available.
     *
     * Falls back to a direct build when the cache store is unavailable
     * (e.g. missing `cache` table in production) so /sitemap.xml never 500s
     * while /robots.txt stays reachable.
     */
    public static function remember(): string
    {
        try {
            return Cache::remember(self::KEY, self::TTL, fn (): string => self::build());
        } catch (Throwable $throwable) {
            report($throwable);

            return self::build();
        }
    }

    public static function forget(): void
    {
        try {
            Cache::forget(self::KEY);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    public static function build(): string
    {
        $sitemap = Sitemap::create()
            ->add(self::staticUrl('/', 1.0, Url::CHANGE_FREQUENCY_DAILY, self::homepageLastModified()))
            ->add(self::staticUrl('/secteurs', 0.9, Url::CHANGE_FREQUENCY_WEEKLY, self::latestUpdate(Sector::class)))
            ->add(self::staticUrl('/expertises', 0.9, Url::CHANGE_FREQUENCY_WEEKLY, self::latestUpdate(Expertise::class)))
            ->add(self::staticUrl('/realisations', 0.9, Url::CHANGE_FREQUENCY_WEEKLY, self::latestUpdate(Project::class)))
            ->add(self::staticUrl('/contact', 0.7, Url::CHANGE_FREQUENCY_MONTHLY, self::latestUpdate(Setting::class)))
            ->add(self::staticUrl('/mentions-legales', 0.3, Url::CHANGE_FREQUENCY_YEARLY, self::pageLastModified('mentions-legales')))
            ->add(self::staticUrl('/politique-de-confidentialite', 0.3, Url::CHANGE_FREQUENCY_YEARLY, self::pageLastModified('politique-de-confidentialite')));

        Project::published()->latest()->each(
            fn (Project $project) => $sitemap->add(Url::create("/realisations/{$project->slug}")->setPriority(0.7)->setLastModificationDate($project->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
        );

        Page::published()
            ->whereNotIn('slug', ['mentions-legales', 'politique-de-confidentialite'])
            ->get()
            ->each(fn (Page $page) => $sitemap->add(Url::create("/pages/{$page->slug}")->setPriority(0.5)->setLastModificationDate($page->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)));

        Sector::active()->ordered()->each(
            fn (Sector $sector) => $sitemap->add(Url::create("/secteurs/{$sector->slug}")->setPriority(0.8)->setLastModificationDate($sector->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
        );

        return $sitemap->render();
    }

    /**
     * Build a static URL entry, with lastmod only when a real timestamp exists.
     *
     * Google ignores sitemaps with auto-generated lastmod values, so a missing
     * timestamp omits the tag instead of faking one.
     */
    private static function staticUrl(string $path, float $priority, string $changeFrequency, ?Carbon $lastModified): Url
    {
        $url = Url::create($path)->setPriority($priority)->setChangeFrequency($changeFrequency);

        if ($lastModified !== null) {
            $url->setLastModificationDate($lastModified);
        }

        return $url;
    }

    /**
     * Homepage signal: latest update across every content type it displays.
     */
    private static function homepageLastModified(): ?Carbon
    {
        $candidates = [
            self::latestUpdate(Sector::class),
            self::latestUpdate(Expertise::class),
            self::latestUpdate(Project::class),
            self::latestUpdate(Page::class),
            self::latestUpdate(Testimonial::class),
            self::latestUpdate(Statistic::class),
            self::latestUpdate(Setting::class),
        ];

        $latest = null;

        foreach ($candidates as $candidate) {
            if ($candidate !== null && ($latest === null || $candidate->greaterThan($latest))) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private static function pageLastModified(string $slug): ?Carbon
    {
        $updatedAt = Page::query()->where('slug', $slug)->where('is_published', true)->value('updated_at');

        return self::toCarbon($updatedAt);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function latestUpdate(string $modelClass): ?Carbon
    {
        return self::toCarbon($modelClass::query()->max('updated_at'));
    }

    /**
     * Normalize an aggregate timestamp (string or date object depending on driver).
     */
    private static function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        return is_string($value) ? Carbon::parse($value) : null;
    }
}
