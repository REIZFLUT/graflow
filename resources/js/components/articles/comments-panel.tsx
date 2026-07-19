import { router } from '@inertiajs/react';
import { Check, CornerDownRight, RotateCcw } from 'lucide-react';
import { useEffect, useState } from 'react';
import ArticleCommentController from '@/actions/App/Http/Controllers/ArticleCommentController';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ArticleCommentThread } from '@/types';

type CommentsPanelProps = {
    articleId: number;
    threads: ArticleCommentThread[];
    presentThreadIds: string[];
    activeThreadId: string | null;
    onSelectThread: (threadId: string) => void;
    commentsVisible: boolean;
    onCommentsVisibleChange: (visible: boolean) => void;
    canComment: boolean;
};

function orderThreads(
    threads: ArticleCommentThread[],
    presentThreadIds: string[],
): ArticleCommentThread[] {
    return [...threads].sort((left, right) => {
        const leftIndex = presentThreadIds.indexOf(left.id);
        const rightIndex = presentThreadIds.indexOf(right.id);
        const leftRank = leftIndex === -1 ? Number.MAX_SAFE_INTEGER : leftIndex;
        const rightRank =
            rightIndex === -1 ? Number.MAX_SAFE_INTEGER : rightIndex;

        if (leftRank !== rightRank) {
            return leftRank - rightRank;
        }

        return left.created_at.localeCompare(right.created_at);
    });
}

function CommentThreadCard({
    articleId,
    thread,
    isActive,
    isOrphaned,
    canComment,
    onSelect,
}: {
    articleId: number;
    thread: ArticleCommentThread;
    isActive: boolean;
    isOrphaned: boolean;
    canComment: boolean;
    onSelect: () => void;
}) {
    const { t, locale } = useTranslation();
    const [replyBody, setReplyBody] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const isResolved = thread.resolved_at !== null;

    const submitReply = () => {
        if (replyBody.trim() === '' || submitting) {
            return;
        }

        setSubmitting(true);
        router.post(
            ArticleCommentController.reply.url({
                article: articleId,
                thread: thread.id,
            }),
            { body: replyBody.trim() },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setReplyBody(''),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const toggleResolved = () => {
        const action = isResolved
            ? ArticleCommentController.reopen
            : ArticleCommentController.resolve;

        router.patch(
            action.url({ article: articleId, thread: thread.id }),
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div
            data-comment-thread-item={thread.id}
            className={cn(
                'space-y-3 rounded-md border border-border/60 bg-muted/20 p-3 transition-colors',
                isActive && 'border-border bg-muted/50 ring-1 ring-border/60',
                isResolved && 'opacity-70',
            )}
        >
            <button
                type="button"
                onClick={onSelect}
                className="w-full text-left"
            >
                <span
                    className={cn(
                        'line-clamp-2 border-l-2 border-amber-400 pl-2 text-xs text-muted-foreground italic',
                        isOrphaned && 'border-muted-foreground/40 line-through',
                    )}
                >
                    {thread.anchor_text?.trim()
                        ? thread.anchor_text
                        : t('articles.comment.no_anchor')}
                </span>
            </button>

            <ol className="space-y-3">
                {thread.comments.map((comment) => (
                    <li key={comment.id} className="space-y-1">
                        <div className="flex items-baseline justify-between gap-2">
                            <span className="text-xs font-medium text-foreground">
                                {comment.user.name}
                            </span>
                            <span className="shrink-0 text-[0.65rem] text-muted-foreground">
                                {formatDateTime(comment.created_at, locale)}
                            </span>
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap text-foreground">
                            {comment.body}
                        </p>
                    </li>
                ))}
            </ol>

            {isResolved && (
                <p className="text-[0.65rem] text-muted-foreground">
                    {t('articles.comment.resolved_by', {
                        name: thread.resolved_by?.name ?? t('common.unknown'),
                    })}
                </p>
            )}

            {canComment && (
                <div className="space-y-2 border-t border-border/50 pt-3">
                    {!isResolved && (
                        <div className="space-y-2">
                            <textarea
                                value={replyBody}
                                onChange={(event) =>
                                    setReplyBody(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (
                                        (event.metaKey || event.ctrlKey) &&
                                        event.key === 'Enter'
                                    ) {
                                        event.preventDefault();
                                        submitReply();
                                    }
                                }}
                                rows={2}
                                placeholder={t(
                                    'articles.comment.reply_placeholder',
                                )}
                                className="w-full resize-none rounded-md border border-input bg-background px-2.5 py-1.5 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <div className="flex items-center justify-between gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 px-2 text-xs"
                                    onClick={toggleResolved}
                                >
                                    <Check className="size-3.5" />
                                    {t('articles.comment.resolve')}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    className="h-7 px-2 text-xs"
                                    disabled={
                                        replyBody.trim() === '' || submitting
                                    }
                                    onClick={submitReply}
                                >
                                    <CornerDownRight className="size-3.5" />
                                    {t('articles.comment.reply')}
                                </Button>
                            </div>
                        </div>
                    )}
                    {isResolved && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            onClick={toggleResolved}
                        >
                            <RotateCcw className="size-3.5" />
                            {t('articles.comment.reopen')}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}

export default function CommentsPanel({
    articleId,
    threads,
    presentThreadIds,
    activeThreadId,
    onSelectThread,
    commentsVisible,
    onCommentsVisibleChange,
    canComment,
}: CommentsPanelProps) {
    const { t } = useTranslation();
    const orderedThreads = orderThreads(threads, presentThreadIds);

    useEffect(() => {
        if (!activeThreadId) {
            return;
        }

        const element = document.querySelector<HTMLElement>(
            `[data-comment-thread-item="${CSS.escape(activeThreadId)}"]`,
        );

        element?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }, [activeThreadId]);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3 rounded-md border border-border/60 bg-muted/20 px-3 py-2">
                <span className="text-sm text-foreground">
                    {t('articles.comment.show_in_text')}
                </span>
                <Switch
                    checked={commentsVisible}
                    onCheckedChange={onCommentsVisibleChange}
                    aria-label={t('articles.comment.show_in_text')}
                />
            </div>

            {orderedThreads.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('articles.comment.empty')}
                </p>
            ) : (
                <div className="space-y-3">
                    {orderedThreads.map((thread) => (
                        <CommentThreadCard
                            key={thread.id}
                            articleId={articleId}
                            thread={thread}
                            isActive={thread.id === activeThreadId}
                            isOrphaned={!presentThreadIds.includes(thread.id)}
                            canComment={canComment}
                            onSelect={() => onSelectThread(thread.id)}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
