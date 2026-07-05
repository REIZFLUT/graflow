import katex from 'katex';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export type MathDialogVariant = 'inline' | 'block';
export type MathDialogMode = 'create' | 'edit';

type MathPreviewProps = {
    latex: string;
    displayMode: boolean;
    emptyLabel: string;
};

function MathPreview({ latex, displayMode, emptyLabel }: MathPreviewProps) {
    const previewRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const element = previewRef.current;

        if (!element) {
            return;
        }

        if (latex.trim() === '') {
            element.textContent = emptyLabel;
            element.classList.add('text-muted-foreground/60');

            return;
        }

        element.classList.remove('text-muted-foreground/60');

        try {
            katex.render(latex, element, {
                throwOnError: false,
                displayMode,
            });
        } catch {
            element.textContent = latex;
        }
    }, [displayMode, emptyLabel, latex]);

    return (
        <div
            className={cn(
                'min-h-16 rounded-md border border-border/60 bg-muted/20 px-4 py-3',
                displayMode ? 'text-center' : 'text-left',
            )}
        >
            <div ref={previewRef} className="overflow-x-auto text-sm" />
        </div>
    );
}

type MathDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    value: string;
    onChange: (value: string) => void;
    onSave: () => void;
    onRemove?: () => void;
    variant: MathDialogVariant;
    mode: MathDialogMode;
};

export default function MathDialog({
    open,
    onOpenChange,
    value,
    onChange,
    onSave,
    onRemove,
    variant,
    mode,
}: MathDialogProps) {
    const { t } = useTranslation();
    const isBlock = variant === 'block';

    const title =
        mode === 'create'
            ? isBlock
                ? t('editor.math.insert_block')
                : t('editor.math.insert_inline')
            : isBlock
              ? t('editor.math.edit_block')
              : t('editor.math.edit_inline');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {t('editor.math.description', {
                            inline_example: 'm^2',
                            block_example: 'E = mc^2',
                        })}
                    </DialogDescription>
                </DialogHeader>

                <textarea
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    rows={4}
                    placeholder={t('editor.math.placeholder')}
                    spellCheck={false}
                    className="min-h-24 w-full resize-y rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
                />

                <MathPreview
                    latex={value}
                    displayMode={isBlock}
                    emptyLabel={t('editor.math.preview_empty')}
                />

                <DialogFooter className="gap-2 sm:justify-between">
                    {mode === 'edit' && onRemove ? (
                        <Button
                            type="button"
                            variant="ghost"
                            className="text-destructive hover:text-destructive"
                            onClick={onRemove}
                        >
                            {t('common.remove')}
                        </Button>
                    ) : (
                        <span />
                    )}
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => onOpenChange(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="button"
                            onClick={onSave}
                            disabled={value.trim() === ''}
                        >
                            {t('common.save')}
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
