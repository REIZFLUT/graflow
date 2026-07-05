import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import type { ArticleMediaFormData } from '@/hooks/use-article-media';
import type { ArticleMedia } from '@/types';
import { useEffect, useState } from 'react';

type ArticleImageDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'upload' | 'edit';
    media?: ArticleMedia | null;
    uploading?: boolean;
    onUpload?: (
        file: File,
        metadata: ArticleMediaFormData,
    ) => Promise<void>;
    onSave?: (metadata: ArticleMediaFormData) => Promise<void>;
};

export default function ArticleImageDialog({
    open,
    onOpenChange,
    mode,
    media = null,
    uploading = false,
    onUpload,
    onSave,
}: ArticleImageDialogProps) {
    const { t } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [altText, setAltText] = useState('');
    const [copyright, setCopyright] = useState('');
    const [caption, setCaption] = useState('');
    const [validationError, setValidationError] = useState<string | null>(null);

    useEffect(() => {
        if (!open) {
            setFile(null);
            setValidationError(null);

            return;
        }

        if (mode === 'edit' && media) {
            setAltText(media.alt_text);
            setCopyright(media.copyright);
            setCaption(media.caption ?? '');
        } else {
            setAltText('');
            setCopyright('');
            setCaption('');
        }
    }, [open, mode, media]);

    const handleSubmit = async () => {
        if (altText.trim() === '' || copyright.trim() === '') {
            setValidationError(t('articles.media.validation_required'));
            return;
        }

        setValidationError(null);

        const metadata: ArticleMediaFormData = {
            alt_text: altText.trim(),
            copyright: copyright.trim(),
            caption: caption.trim(),
        };

        if (mode === 'upload') {
            if (!file) {
                setValidationError(t('articles.media.validation_no_file'));
                return;
            }

            await onUpload?.(file, metadata);
        } else {
            await onSave?.(metadata);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'upload'
                            ? t('articles.media.upload_title')
                            : t('articles.media.edit_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('articles.media.description')}
                    </DialogDescription>
                </DialogHeader>

                {mode === 'upload' && (
                    <div className="space-y-2">
                        <label
                            htmlFor="article-image-file"
                            className="text-sm font-medium"
                        >
                            {t('articles.media.file_label')}
                        </label>
                        <input
                            id="article-image-file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            onChange={(event) =>
                                setFile(event.target.files?.[0] ?? null)
                            }
                            className="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-medium"
                        />
                    </div>
                )}

                {mode === 'edit' && media && (
                    <div className="overflow-hidden rounded-md border border-border/60">
                        <img
                            src={media.preview_jpeg_url}
                            alt={media.alt_text}
                            className="max-h-48 w-full object-contain bg-muted/30"
                        />
                    </div>
                )}

                <div className="space-y-3">
                    <div className="space-y-2">
                        <label htmlFor="article-image-alt" className="text-sm font-medium">
                            {t('articles.media.alt_label')} *
                        </label>
                        <input
                            id="article-image-alt"
                            type="text"
                            value={altText}
                            onChange={(event) => setAltText(event.target.value)}
                            placeholder={t('articles.media.alt_placeholder')}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
                        />
                    </div>

                    <div className="space-y-2">
                        <label
                            htmlFor="article-image-copyright"
                            className="text-sm font-medium"
                        >
                            {t('articles.media.copyright_label')} *
                        </label>
                        <input
                            id="article-image-copyright"
                            type="text"
                            value={copyright}
                            onChange={(event) =>
                                setCopyright(event.target.value)
                            }
                            placeholder={t('articles.media.copyright_placeholder')}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
                        />
                    </div>

                    <div className="space-y-2">
                        <label
                            htmlFor="article-image-caption"
                            className="text-sm font-medium"
                        >
                            {t('articles.media.caption_label')}
                        </label>
                        <input
                            id="article-image-caption"
                            type="text"
                            value={caption}
                            onChange={(event) => setCaption(event.target.value)}
                            placeholder={t('articles.media.caption_placeholder')}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring/40"
                        />
                    </div>
                </div>

                <InputError message={validationError ?? undefined} />

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => onOpenChange(false)}
                        disabled={uploading}
                    >
                        {t('common.cancel')}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => void handleSubmit()}
                        disabled={uploading}
                    >
                        {uploading ? (
                            <Spinner className="size-4" />
                        ) : mode === 'upload' ? (
                            t('common.upload')
                        ) : (
                            t('common.save')
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
