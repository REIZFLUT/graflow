import { usePage } from '@inertiajs/react';
import { translate  } from '@/lib/i18n';
import type {ReplaceValues} from '@/lib/i18n';

export function useTranslation(): {
    t: (key: string, replace?: ReplaceValues) => string;
    locale: string;
} {
    const { translations, locale } = usePage().props;

    return {
        locale,
        t: (key: string, replace?: ReplaceValues) =>
            translate(translations, key, replace),
    };
}
