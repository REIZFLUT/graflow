import type { DocumentStats } from '@/lib/document-stats';
import { useTranslation } from '@/hooks/use-translation';

type DocumentStatusBarProps = DocumentStats;

export default function DocumentStatusBar({
    words,
    letters,
}: DocumentStatusBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex items-center justify-end gap-4 text-xs tabular-nums text-muted-foreground">
            <span>{t('articles.stats.words', { count: words })}</span>
            <span>{t('articles.stats.letters', { count: letters })}</span>
        </div>
    );
}
