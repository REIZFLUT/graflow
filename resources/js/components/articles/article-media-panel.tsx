import type { Editor } from '@tiptap/react';
import { ImagePlus, Pencil, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { insertArticleImage } from '@/lib/tiptap';
import type { ArticleMedia } from '@/types';

type ArticleMediaPanelProps = {
    editor: Editor | null;
    mediaItems: ArticleMedia[];
    onUploadClick: () => void;
    onEditMedia: (media: ArticleMedia) => void;
    onDeleteMedia: (media: ArticleMedia) => Promise<void>;
    getUsedMediaIds: () => string[];
    canEdit?: boolean;
};

export default function ArticleMediaPanel({
    editor,
    mediaItems,
    onUploadClick,
    onEditMedia,
    onDeleteMedia,
    getUsedMediaIds,
    canEdit = true,
}: ArticleMediaPanelProps) {
    const { t } = useTranslation();
    const usedMediaIds = new Set(getUsedMediaIds());

    if (mediaItems.length === 0) {
        return (
            <div className="flex flex-col items-center gap-4 py-8 text-center">
                <p className="text-sm text-muted-foreground">
                    {t('articles.media.empty')}
                </p>
                {canEdit && (
                    <Button type="button" size="sm" onClick={onUploadClick}>
                        <ImagePlus className="size-4" />
                        {t('articles.media.upload_button')}
                    </Button>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {canEdit && (
                <div className="flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={onUploadClick}
                    >
                        <ImagePlus className="size-4" />
                        {t('articles.media.upload_button')}
                    </Button>
                </div>
            )}

            <div className="grid grid-cols-2 gap-3">
                {mediaItems.map((media) => {
                    const isUsed = usedMediaIds.has(media.id);

                    return (
                        <div
                            key={media.id}
                            className="overflow-hidden rounded-lg border border-border/60 bg-card"
                        >
                            <div className="aspect-[4/3] bg-muted/30">
                                <img
                                    src={media.preview_jpeg_url}
                                    alt={media.alt_text}
                                    className="size-full object-cover"
                                />
                            </div>
                            <div className="space-y-2 p-3">
                                <p className="line-clamp-1 text-xs font-medium">
                                    {media.original_filename}
                                </p>
                                <p className="line-clamp-2 text-xs text-muted-foreground">
                                    {media.alt_text}
                                </p>
                                {isUsed && (
                                    <p className="text-[11px] text-muted-foreground">
                                        {t('articles.media.used_in_article')}
                                    </p>
                                )}
                                {canEdit && (
                                    <div className="flex flex-wrap gap-1">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            className="h-7 px-2 text-xs"
                                            disabled={!editor}
                                            onClick={() =>
                                                editor &&
                                                insertArticleImage(
                                                    editor,
                                                    media,
                                                )
                                            }
                                        >
                                            {t('articles.media.insert')}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            className="size-7"
                                            onClick={() => onEditMedia(media)}
                                        >
                                            <Pencil className="size-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            className="size-7 text-destructive"
                                            onClick={() =>
                                                void onDeleteMedia(media)
                                            }
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
