export type ReplaceValues = Record<string, string | number>;

export type TranslateFn = (
    key: string,
    replace?: ReplaceValues,
) => string;

function resolveTranslation(
    translations: Record<string, unknown>,
    key: string,
): string | null {
    const [domain, ...rest] = key.split('.');

    if (!domain || rest.length === 0) {
        return null;
    }

    let value: unknown = translations[domain];

    for (const part of rest) {
        if (
            value === null ||
            typeof value !== 'object' ||
            !(part in (value as Record<string, unknown>))
        ) {
            return null;
        }

        value = (value as Record<string, unknown>)[part];
    }

    return typeof value === 'string' ? value : null;
}

function applyReplacements(
    value: string,
    replace?: ReplaceValues,
): string {
    if (!replace) {
        return value;
    }

    return Object.entries(replace).reduce(
        (result, [placeholder, replacement]) =>
            result.replaceAll(`:${placeholder}`, String(replacement)),
        value,
    );
}

export function translate(
    translations: Record<string, unknown>,
    key: string,
    replace?: ReplaceValues,
): string {
    const value = resolveTranslation(translations, key);

    if (value === null) {
        return key;
    }

    return applyReplacements(value, replace);
}

export function toIntlLocale(locale: string): string {
    return locale === 'de' ? 'de-DE' : 'en-US';
}

export function formatDateTime(
    value: string | Date,
    locale: string,
    options?: Intl.DateTimeFormatOptions,
): string {
    const date = value instanceof Date ? value : new Date(value);

    return date.toLocaleString(toIntlLocale(locale), options);
}
