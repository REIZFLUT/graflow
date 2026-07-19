import type { Editor } from '@tiptap/react';
import { MessageSquare } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { ArticleCommentThread } from '@/types';

type CommentMarginColumnProps = {
    editor: Editor;
    threads: ArticleCommentThread[];
    activeThreadId: string | null;
    visible: boolean;
    onSelectThread: (threadId: string) => void;
};

type PositionedBubble = {
    threadId: string;
    top: number;
    resolved: boolean;
};

export default function CommentMarginColumn({
    editor,
    threads,
    activeThreadId,
    visible,
    onSelectThread,
}: CommentMarginColumnProps) {
    const { t } = useTranslation();
    const overlayRef = useRef<HTMLDivElement>(null);
    const [bubbles, setBubbles] = useState<PositionedBubble[]>([]);

    const syncBubbles = useCallback(() => {
        if (editor.isDestroyed) {
            return;
        }

        const overlay = overlayRef.current;

        if (!overlay) {
            return;
        }

        const overlayRect = overlay.getBoundingClientRect();
        const resolvedIds = new Set(
            threads
                .filter((thread) => thread.resolved_at !== null)
                .map((thread) => thread.id),
        );

        const seen = new Set<string>();
        const positioned: PositionedBubble[] = [];

        editor.view.dom
            .querySelectorAll<HTMLElement>('[data-comment-thread-id]')
            .forEach((element) => {
                const threadId = element.getAttribute('data-comment-thread-id');

                if (!threadId || seen.has(threadId)) {
                    return;
                }

                seen.add(threadId);

                const rect = element.getBoundingClientRect();

                positioned.push({
                    threadId,
                    top: rect.top - overlayRect.top,
                    resolved: resolvedIds.has(threadId),
                });
            });

        setBubbles(positioned);
    }, [editor, threads]);

    useEffect(() => {
        syncBubbles();

        editor.on('update', syncBubbles);
        editor.on('selectionUpdate', syncBubbles);

        const resizeObserver = new ResizeObserver(syncBubbles);
        resizeObserver.observe(editor.view.dom);

        window.addEventListener('scroll', syncBubbles, true);
        window.addEventListener('resize', syncBubbles);

        return () => {
            editor.off('update', syncBubbles);
            editor.off('selectionUpdate', syncBubbles);
            resizeObserver.disconnect();
            window.removeEventListener('scroll', syncBubbles, true);
            window.removeEventListener('resize', syncBubbles);
        };
    }, [editor, syncBubbles]);

    if (!visible) {
        return null;
    }

    return (
        <div
            ref={overlayRef}
            aria-hidden={bubbles.length === 0}
            className="pointer-events-none absolute inset-0 z-10 hidden sm:block"
        >
            {bubbles.map((bubble) => (
                <button
                    key={bubble.threadId}
                    type="button"
                    aria-label={t('articles.comment.open_thread')}
                    title={t('articles.comment.open_thread')}
                    onClick={() => onSelectThread(bubble.threadId)}
                    style={{ top: bubble.top }}
                    className={cn(
                        'pointer-events-auto absolute -right-8 inline-flex size-6 items-center justify-center rounded-full border shadow-sm transition-colors',
                        bubble.resolved
                            ? 'border-border/60 bg-muted text-muted-foreground'
                            : 'border-amber-300 bg-amber-100 text-amber-700 hover:bg-amber-200 dark:border-amber-500/40 dark:bg-amber-500/20 dark:text-amber-300',
                        bubble.threadId === activeThreadId &&
                            'ring-2 ring-amber-400 ring-offset-1 ring-offset-background',
                    )}
                >
                    <MessageSquare className="size-3.5" />
                </button>
            ))}
        </div>
    );
}
