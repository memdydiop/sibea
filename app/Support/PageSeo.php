<?php

namespace App\Support;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PageSeo
{
    /**
     * Share a page-specific title and meta description with the SEO partial.
     *
     * The title is suffixed with the site name. An empty description shares
     * null so the partial falls back to the global default.
     */
    public static function share(string $pageTitle, ?string $rawDescription = null): void
    {
        View::share('title', $pageTitle.' — '.(string) setting('general.site_name'));

        $description = $rawDescription !== null ? Str::squish($rawDescription) : '';

        View::share('description', $description !== '' ? Str::limit($description, 160, '') : null);
    }

    /**
     * Build a meta description from markdown content.
     *
     * Images, links, headings and emphasis markers are stripped, whitespace
     * is collapsed and the result is limited to 160 characters.
     */
    public static function excerpt(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return null;
        }

        $text = self::replacePattern('/!\[([^\]]*)\]\([^)]*\)/', '$1', $markdown);
        $text = self::replacePattern('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = str_replace(['#', '*', '>', '`', '_'], '', $text);

        $text = Str::squish($text);

        return $text !== '' ? Str::limit($text, 160, '') : null;
    }

    private static function replacePattern(string $pattern, string $replacement, string $subject): string
    {
        $replaced = preg_replace($pattern, $replacement, $subject);

        return is_string($replaced) ? $replaced : $subject;
    }
}
