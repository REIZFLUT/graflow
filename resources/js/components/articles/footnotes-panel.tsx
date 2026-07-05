import type { Editor } from '@tiptap/react';
import { useEffect } from 'react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import {
    focusFootnoteInEditor,
    getFootnotesFromEditor,
    type ArticleFootnote,
} from '@/lib/tiptap/footnote-utils';
import { cn } from '@/lib/utils';

type FootnotesPanelProps = {
    editor: Editor | null;
    onEditFootnote: (footnote: ArticleFootnote) => void;
    onRemoveFootnote: (footnote: ArticleFootnote) => void;
    onFocusFootnote?: (footnote: ArticleFootnote) => void;
    focusedFootnoteId?: string | null;
    className?: string;
};

export default function FootnotesPanel({
    editor,
    onEditFootnote,
    onRemoveFootnote,
    onFocusFootnote,
    focusedFootnoteId = null,
    className,
}: FootnotesPanelProps) {
    const { t } = useTranslation();

    useEffect(() => {
        if (!focusedFootnoteId) {
            return;
        }

        const element = document.querySelector<HTMLElement>(
            `[data-footnote-item="${focusedFootnoteId}"]`,
        );

        element?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }, [focusedFootnoteId]);

    if (!editor) {
        return null;
    }

    const footnotes = getFootnotesFromEditor(editor);

    const handleFocusInEditor = (footnote: ArticleFootnote) => {
        focusFootnoteInEditor(editor, footnote);
        onFocusFootnote?.(footnote);
    };

    if (footnotes.length === 0) {
        return (
            <p className={cn('text-sm text-muted-foreground', className)}>
                {t('articles.footnote.empty')}
            </p>
        );
    }

    return (
        <ol className={cn('space-y-4', className)}>
            {footnotes.map((footnote, index) => {
                const isFocused = footnote.id === focusedFootnoteId;

                return (
                    <li
                        key={footnote.id}
                        data-footnote-item={footnote.id}
                        className={cn(
                            'space-y-2 rounded-md border-b border-border/50 pb-4 transition-colors last:border-b-0 last:pb-0',
                            isFocused &&
                                'border-border bg-muted/50 px-3 py-3 ring-1 ring-border/60',
                        )}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <button
                                type="button"
                                onClick={() => handleFocusInEditor(footnote)}
                                className={cn(
                                    'min-w-0 text-left text-xs font-medium text-muted-foreground transition-colors hover:text-foreground',
                                    isFocused && 'text-foreground',
                                )}
                            >
                                {index + 1}.{' '}
                                {t('articles.footnote.item_reference', {
                                    excerpt: footnote.excerpt,
                                })}
                            </button>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 px-2 text-xs"
                                    onClick={() => {
                                        handleFocusInEditor(footnote);
                                        onEditFootnote(footnote);
                                    }}
                                >
                                    {t('common.edit')}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                                    onClick={() => onRemoveFootnote(footnote)}
                                >
                                    <Trash2 className="size-3.5" />
                                    {t('common.remove')}
                                </Button>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => handleFocusInEditor(footnote)}
                            className="w-full text-left text-sm leading-relaxed text-foreground transition-colors hover:text-foreground/80"
                        >
                            {footnote.content}
                        </button>
                    </li>
                );
            })}
        </ol>
    );
}
