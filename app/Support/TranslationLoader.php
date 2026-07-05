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
        'messages',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function load(): array
    {
        $locale = app()->getLocale();

        return cache()->rememberForever("translations.{$locale}", function () use ($locale): array {
            $translations = [];

            foreach (self::DOMAINS as $domain) {
                $translations[$domain] = trans($domain, [], $locale);
            }

            return $translations;
        });
    }

    public static function clearCache(): void
    {
        foreach (['en', 'de'] as $locale) {
            cache()->forget("translations.{$locale}");
        }
    }
}
