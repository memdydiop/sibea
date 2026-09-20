<?php

namespace App\Models;

use App\Concerns\AddsMediaFromUploads;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
#[Fillable(['key', 'value'])]
class Setting extends Model implements HasMedia
{
    use AddsMediaFromUploads, InteractsWithMedia;

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();

        if (array_key_exists($key, $settings) && $settings[$key] !== null) {
            return $settings[$key];
        }

        if ($default !== null) {
            return $default;
        }

        return config('site.defaults', [])[$key] ?? null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function getArray(string $key): array
    {
        $value = static::allCached()[$key] ?? null;

        if ($value !== null) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return config('site.arrays', [])[$key] ?? [];
    }

    /**
     * @var array<string, string>
     */
    protected static array $mediaMemo = [];

    public static function mediaUrl(string $key, ?string $conversion = null): string
    {
        $memoKey = $key.'.'.($conversion ?? 'orig');

        if (isset(static::$mediaMemo[$memoKey])) {
            return static::$mediaMemo[$memoKey];
        }

        return static::$mediaMemo[$memoKey] = Cache::remember("site.settings.media.{$memoKey}", 3600, function () use ($key, $conversion): string {
            $media = static::query()->where('key', $key)->first()?->getFirstMedia('file');

            if ($media !== null) {
                if ($conversion !== null && $media->hasGeneratedConversion($conversion)) {
                    return $media->getUrl($conversion);
                }

                return $media->getUrl();
            }

            return asset(config('site.visuals', [])[$key] ?? 'images/logo-sibea.png');
        });
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::flushCache();
    }

    public static function forgetMediaCache(string $key): void
    {
        Cache::forget("site.settings.media.{$key}.orig");
        Cache::forget("site.settings.media.{$key}.hero");
        unset(static::$mediaMemo["{$key}.orig"], static::$mediaMemo["{$key}.hero"]);
    }

    public static function putFile(string $key, UploadedFile $file): void
    {
        $setting = static::firstOrCreate(['key' => $key]);
        $setting->clearMediaCollection('file');
        static::addMediaFromUpload($setting, $file, 'file');
        static::forgetMediaCache($key);
        static::flushCache();
    }

    public static function removeFile(string $key): void
    {
        static::query()->where('key', $key)->first()?->clearMediaCollection('file');
        static::forgetMediaCache($key);
        static::flushCache();
    }

    /**
     * @return array<string, string|null>
     */
    protected static function allCached(): array
    {
        return Cache::rememberForever('site.settings', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function flushCache(): void
    {
        static::$mediaMemo = [];
        Cache::forget('site.settings');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')->nonQueued()->fit(Fit::Max, 1920, 1080)->format('webp');
    }
}
