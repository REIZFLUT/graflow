import type { Editor } from '@tiptap/react';
import { Check, X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { getSpellCheckMatchById } from '@/lib/tiptap';
import type { MappedSpellCheckMatch } from '@/lib/tiptap';
import { cn } from '@/lib/utils';

type SpellCheckPopoverProps = {
    editor: Editor | null;
    matchId: string | null;
    anchorRect: DOMRect | null;
    onClose: () => void;
    onFocusMatch?: (match: MappedSpellCheckMatch) => void;
};

export default function SpellCheckPopover({
    editor,
    matchId,
    anchorRect,
    onClose,
    onFocusMatch,
}: SpellCheckPopoverProps) {
    const { t } = useTranslation();
    const popoverRef = useRef<HTMLDivElement>(null);
    const match =
        editor && matchId ? getSpellCheckMatchById(editor, matchId) : null;

    useEffect(() => {
        if (!matchId) {
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

            if (target.closest(`[data-spellcheck-id="${matchId}"]`)) {
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
    }, [matchId, onClose]);

    if (!editor || !match || !anchorRect) {
        return null;
    }

    const top = Math.min(anchorRect.bottom + 8, window.innerHeight - 12);
    const left = Math.min(
        Math.max(12, anchorRect.left),
        window.innerWidth - 292,
    );

    return (
        <div
            ref={popoverRef}
            role="dialog"
            aria-label={match.shortMessage}
            className={cn(
                'fixed z-50 w-72 rounded-md border border-border bg-popover p-3 text-popover-foreground shadow-md',
            )}
            style={{ top, left }}
        >
            <div className="mb-2 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-xs font-medium text-muted-foreground">
                        {match.shortMessage}
                    </p>
                    <p className="mt-1 text-sm leading-snug text-foreground">
                        {match.message}
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

            {match.replacements.length > 0 ? (
                <div className="flex flex-wrap gap-1.5">
                    {match.replacements.map((replacement) => (
                        <Button
                            key={`${match.id}-${replacement}`}
                            type="button"
                            variant="secondary"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            onClick={() => {
                                onFocusMatch?.(match);
                                editor.commands.applySpellCheckReplacement(
                                    match.id,
                                    replacement,
                                );
                                onClose();
                            }}
                        >
                            <Check className="size-3.5" />
                            {replacement}
                        </Button>
                    ))}
                </div>
            ) : (
                <p className="text-xs text-muted-foreground">
                    {t('editor.spellcheck.no_suggestions')}
                </p>
            )}

            <div className="mt-3 flex justify-end">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                    onClick={() => {
                        editor.commands.dismissSpellCheckMatch(match.id);
                        onClose();
                    }}
                >
                    <X className="size-3.5" />
                    {t('editor.spellcheck.dismiss')}
                </Button>
            </div>
        </div>
    );
}
