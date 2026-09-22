<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Project;
use App\Models\Sector;
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
            ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create('/secteurs')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/expertises')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/realisations')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/contact')->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY))
            ->add(Url::create('/mentions-legales')->setPriority(0.3)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create('/politique-de-confidentialite')->setPriority(0.3)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));

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
}
