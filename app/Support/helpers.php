<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('setting_array')) {
    /**
     * @return array<int, array<string, string>>
     */
    function setting_array(string $key): array
    {
        return Setting::getArray($key);
    }
}

if (! function_exists('setting_media_url')) {
    function setting_media_url(string $key, ?string $conversion = null): string
    {
        return Setting::mediaUrl($key, $conversion);
    }
}
