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

type FootnoteDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    value: string;
    onChange: (value: string) => void;
    onSave: () => void;
    onRemove?: () => void;
    excerpt?: string;
    mode: 'create' | 'edit';
};

export default function FootnoteDialog({
    open,
    onOpenChange,
    value,
    onChange,
    onSave,
    onRemove,
    excerpt,
    mode,
}: FootnoteDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create'
                            ? t('articles.footnote.add_title')
                            : t('articles.footnote.edit_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {excerpt
                            ? t('articles.footnote.item_reference', {
                                  excerpt,
                              })
                            : t('articles.footnote.select_text_first')}
                    </DialogDescription>
                </DialogHeader>

                <textarea
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    rows={5}
                    placeholder={t('articles.footnote.placeholder')}
                    className="min-h-28 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
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
