import {
    GitBranch,
    History,
    Image,
    MessageSquare,
    SquareAsterisk,
} from 'lucide-react';
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
    footnoteCount?: number;
    mediaCount?: number;
    commentsCount?: number;
    showComments?: boolean;
    onFootnotesClick?: () => void;
    onMediaClick?: () => void;
    onHistoryClick?: () => void;
    onVersionsClick?: () => void;
    onCommentsClick?: () => void;
};

export default function ArticleEditorFooter({
    words,
    letters,
    articleId,
    currentAssignee = null,
    submissionDeadline = null,
    targetCharacterCount = null,
    versionsCount = 0,
    footnoteCount = 0,
    mediaCount = 0,
    commentsCount = 0,
    showComments = false,
    onFootnotesClick,
    onMediaClick,
    onHistoryClick,
    onVersionsClick,
    onCommentsClick,
}: ArticleEditorFooterProps) {
    const { t } = useTranslation();

    return (
        <div className="flex w-full items-center justify-between gap-4">
            <div className="flex shrink-0 items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2 text-xs"
                    onClick={onFootnotesClick}
                >
                    <SquareAsterisk className="size-3.5" />
                    {t('articles.editor.footnotes')}
                    {footnoteCount > 0 && (
                        <Badge
                            variant="secondary"
                            className="ml-1 h-5 min-w-5 px-1"
                        >
                            {footnoteCount}
                        </Badge>
                    )}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2 text-xs"
                    onClick={onMediaClick}
                >
                    <Image className="size-3.5" />
                    {t('articles.editor.media')}
                    {mediaCount > 0 && (
                        <Badge
                            variant="secondary"
                            className="ml-1 h-5 min-w-5 px-1"
                        >
                            {mediaCount}
                        </Badge>
                    )}
                </Button>
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
                        {showComments && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-7 px-2 text-xs"
                                onClick={onCommentsClick}
                            >
                                <MessageSquare className="size-3.5" />
                                {t('articles.editor.comments')}
                                {commentsCount > 0 && (
                                    <Badge
                                        variant="secondary"
                                        className="ml-1 h-5 min-w-5 px-1"
                                    >
                                        {commentsCount}
                                    </Badge>
                                )}
                            </Button>
                        )}
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
