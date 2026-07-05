import type { TranslateFn } from '@/lib/i18n';

export const articleStatuses = ['draft', 'published', 'archived'] as const;

export type ArticleStatusValue = (typeof articleStatuses)[number];

export function getArticleStatusLabel(
    status: string,
    t: TranslateFn,
): string {
    if (isArticleStatusValue(status)) {
        return t(`articles.status.${status}`);
    }

    return status;
}

export function isArticleStatusValue(
    status: string,
): status is ArticleStatusValue {
    return articleStatuses.includes(status as ArticleStatusValue);
}
