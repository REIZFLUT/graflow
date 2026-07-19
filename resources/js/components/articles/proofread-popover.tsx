import type { Editor } from '@tiptap/react';
import { Check, X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { getProofreadIssueById } from '@/lib/tiptap';
import type { MappedProofreadIssue, ProofreadCategory } from '@/lib/tiptap';
import { cn } from '@/lib/utils';

type ProofreadPopoverProps = {
    editor: Editor | null;
    issueId: string | null;
    anchorRect: DOMRect | null;
    onClose: () => void;
    onFocusIssue?: (issue: MappedProofreadIssue) => void;
};

const CATEGORY_TRANSLATION_KEYS: Record<ProofreadCategory, string> = {
    unfinished_sentence: 'editor.proofread.categories.unfinished_sentence',
    illogical_sentence: 'editor.proofread.categories.illogical_sentence',
    word_repetition: 'editor.proofread.categories.word_repetition',
    colloquialism: 'editor.proofread.categories.colloquialism',
    language_pattern: 'editor.proofread.categories.language_pattern',
    other: 'editor.proofread.categories.other',
};

export default function ProofreadPopover({
    editor,
    issueId,
    anchorRect,
    onClose,
    onFocusIssue,
}: ProofreadPopoverProps) {
    const { t } = useTranslation();
    const popoverRef = useRef<HTMLDivElement>(null);
    const issue =
        editor && issueId ? getProofreadIssueById(editor, issueId) : null;

    useEffect(() => {
        if (!issueId) {
            return;
        }

        const handlePointerDown = (event: PointerEvent) => {
            const target = event.target as HTMLElement | null;

            if (!target) {
                return;
            }

            if (popoverRef.current?.contains(target)) {
                return;
            }

            if (target.closest(`[data-proofread-id="${issueId}"]`)) {
                return;
            }

            onClose();
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [issueId, onClose]);

    if (!editor || !issue || !anchorRect) {
        return null;
    }

    const top = Math.min(anchorRect.bottom + 8, window.innerHeight - 12);
    const left = Math.min(
        Math.max(12, anchorRect.left),
        window.innerWidth - 312,
    );

    return (
        <div
            ref={popoverRef}
            role="dialog"
            aria-label={t(CATEGORY_TRANSLATION_KEYS[issue.category])}
            className={cn(
                'fixed z-50 w-78 rounded-md border border-border bg-popover p-3 text-popover-foreground shadow-md',
            )}
            style={{ top, left, width: '19rem' }}
        >
            <div className="mb-2 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <Badge
                        variant="secondary"
                        className="mb-1 text-[0.65rem] font-medium"
                    >
                        {t(CATEGORY_TRANSLATION_KEYS[issue.category])}
                    </Badge>
                    <p className="text-sm leading-snug text-foreground">
                        {issue.message}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 shrink-0 px-2"
                    onClick={onClose}
                    aria-label={t('common.close')}
                >
                    <X className="size-3.5" />
                </Button>
            </div>

            {issue.suggestion.length > 0 && (
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    className="h-auto w-full justify-start whitespace-normal px-2 py-1 text-left text-xs"
                    onClick={() => {
                        onFocusIssue?.(issue);
                        editor.commands.applyProofreadSuggestion(
                            issue.id,
                            issue.suggestion,
                        );
                        onClose();
                    }}
                >
                    <Check className="size-3.5 shrink-0" />
                    {issue.suggestion}
                </Button>
            )}

            <div className="mt-3 flex justify-end">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                    onClick={() => {
                        editor.commands.dismissProofreadIssue(issue.id);
                        onClose();
                    }}
                >
                    <X className="size-3.5" />
                    {t('editor.proofread.dismiss')}
                </Button>
            </div>
        </div>
    );
}
