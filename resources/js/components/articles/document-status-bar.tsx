import { useTranslation } from '@/hooks/use-translation';
import type { DocumentStats } from '@/lib/document-stats';
import { formatDateTime } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ArticleUser } from '@/types';

const editorOpenedAt = Date.now();

type DocumentStatusBarProps = DocumentStats & {
    currentAssignee?: ArticleUser | null;
    submissionDeadline?: string | null;
    targetCharacterCount?: number | null;
};

export default function DocumentStatusBar({
    words,
    letters,
    currentAssignee = null,
    submissionDeadline = null,
    targetCharacterCount = null,
}: DocumentStatusBarProps) {
    const { t, locale } = useTranslation();
    const deadline = submissionDeadline ? new Date(submissionDeadline) : null;
    const isOverdue =
        deadline !== null &&
        !Number.isNaN(deadline.getTime()) &&
        deadline.getTime() < editorOpenedAt;
    const progress =
        targetCharacterCount && targetCharacterCount > 0
            ? Math.round((letters / targetCharacterCount) * 100)
            : null;
    const progressTone =
        progress === null
            ? ''
            : progress > 110
              ? 'text-destructive'
              : progress >= 100
                ? 'text-emerald-700 dark:text-emerald-400'
                : 'text-amber-700 dark:text-amber-400';

    return (
        <div className="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-xs text-muted-foreground tabular-nums">
            <span>
                {t('articles.stats.assignee')}:{' '}
                <span className="text-foreground">
                    {currentAssignee?.name ?? t('common.em_dash')}
                </span>
            </span>
            <span className={cn(isOverdue && 'font-medium text-destructive')}>
                {t('articles.stats.deadline')}:{' '}
                {deadline
                    ? formatDateTime(deadline, locale, {
                          dateStyle: 'medium',
                      })
                    : t('common.em_dash')}
                {isOverdue && ` · ${t('articles.stats.overdue')}`}
            </span>
            <span>{t('articles.stats.words', { count: words })}</span>
            <span className={cn('font-medium', progressTone)}>
                {targetCharacterCount && targetCharacterCount > 0
                    ? t('articles.stats.letters_target', {
                          count: letters,
                          target: targetCharacterCount,
                          progress: progress ?? 0,
                      })
                    : t('articles.stats.letters', { count: letters })}
            </span>
        </div>
    );
}
