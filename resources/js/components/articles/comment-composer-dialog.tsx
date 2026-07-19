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

type CommentComposerDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    value: string;
    onChange: (value: string) => void;
    onSave: () => void;
    excerpt?: string;
    submitting?: boolean;
};

export default function CommentComposerDialog({
    open,
    onOpenChange,
    value,
    onChange,
    onSave,
    excerpt,
    submitting = false,
}: CommentComposerDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {t('articles.comment.add_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {excerpt
                            ? t('articles.comment.item_reference', { excerpt })
                            : t('articles.comment.select_text_first')}
                    </DialogDescription>
                </DialogHeader>

                <textarea
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    onKeyDown={(event) => {
                        if (
                            (event.metaKey || event.ctrlKey) &&
                            event.key === 'Enter'
                        ) {
                            event.preventDefault();
                            onSave();
                        }
                    }}
                    rows={5}
                    autoFocus
                    placeholder={t('articles.comment.placeholder')}
                    className="min-h-28 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
                />

                <DialogFooter className="gap-2">
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
                        disabled={value.trim() === '' || submitting}
                    >
                        {t('common.save')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
