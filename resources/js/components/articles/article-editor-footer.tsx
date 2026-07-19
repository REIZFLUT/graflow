import { GitBranch, History } from 'lucide-react';
import DocumentStatusBar from '@/components/articles/document-status-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { DocumentStats } from '@/lib/document-stats';
import type { ArticleUser } from '@/types';

type ArticleEditorFooterProps = DocumentStats & {
    articleId?: number;
    currentAssignee?: ArticleUser | null;
    submissionDeadline?: string | null;
    targetCharacterCount?: number | null;
    versionsCount?: number;
    onHistoryClick?: () => void;
    onVersionsClick?: () => void;
};

export default function ArticleEditorFooter({
    words,
    letters,
    articleId,
    currentAssignee = null,
    submissionDeadline = null,
    targetCharacterCount = null,
    versionsCount = 0,
    onHistoryClick,
    onVersionsClick,
}: ArticleEditorFooterProps) {
    const { t } = useTranslation();

    return (
        <div className="flex w-full items-center justify-between gap-4">
            <div className="flex shrink-0 items-center gap-1">
                {articleId !== undefined && (
                    <>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            onClick={onHistoryClick}
                        >
                            <GitBranch className="size-3.5" />
                            {t('articles.editor.history')}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            onClick={onVersionsClick}
                        >
                            <History className="size-3.5" />
                            {t('articles.editor.versions')}
                            {versionsCount > 0 && (
                                <Badge
                                    variant="secondary"
                                    className="ml-1 h-5 min-w-5 px-1"
                                >
                                    {versionsCount}
                                </Badge>
                            )}
                        </Button>
                    </>
                )}
            </div>

            <DocumentStatusBar
                words={words}
                letters={letters}
                currentAssignee={currentAssignee}
                submissionDeadline={submissionDeadline}
                targetCharacterCount={targetCharacterCount}
            />
        </div>
    );
}
