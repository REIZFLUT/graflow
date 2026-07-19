<?php

namespace App\Support;

class TranslationLoader
{
    /**
     * @var list<string>
     */
    private const DOMAINS = [
        'common',
        'nav',
        'auth',
        'settings',
        'dashboard',
        'articles',
        'publications',
        'editor',
        'users',
        'messages',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function load(): array
    {
        $locale = app()->getLocale();
        $cacheKey = "translations.{$locale}";
        $version = self::cacheVersion();

        /** @var array{version?: string, data?: array<string, array<string, mixed>>}|null $cached */
        $cached = cache()->get($cacheKey);

        if (
            is_array($cached)
            && isset($cached['version'], $cached['data'])
            && $cached['version'] === $version
        ) {
            return $cached['data'];
        }

        $data = self::loadFresh($locale);

        cache()->forever($cacheKey, [
            'version' => $version,
            'data' => $data,
        ]);

        return $data;
    }

    public static function clearCache(): void
    {
        foreach (['en', 'de'] as $locale) {
            cache()->forget("translations.{$locale}");
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function loadFresh(string $locale): array
    {
        $translations = [];

        foreach (self::DOMAINS as $domain) {
            $translations[$domain] = trans($domain, [], $locale);
        }

        return $translations;
    }

    private static function cacheVersion(): string
    {
        $newestMtime = 0;

        foreach (self::DOMAINS as $domain) {
            foreach (['en', 'de'] as $locale) {
                $path = lang_path("{$locale}/{$domain}.php");

                if (is_file($path)) {
                    $newestMtime = max($newestMtime, filemtime($path) ?: 0);
                }
            }
        }

        return (string) $newestMtime;
    }
}
