import type { TranslateFn } from '@/lib/i18n';
import type { ArticleStatus } from '@/types';

export const articleStatuses = [
    'planned',
    'authoring',
    'manuscript_submitted',
    'product_manager_correction',
    'revision_requested',
    'revision',
    'editorial_work',
    'ready_for_publication',
    'published',
] as const satisfies readonly ArticleStatus[];

export function getArticleStatusLabel(
    status: ArticleStatus,
    t: TranslateFn,
): string {
    return t(`articles.status.${status}`);
}
