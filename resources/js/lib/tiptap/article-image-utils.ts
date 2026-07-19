import { NodeSelection } from '@tiptap/pm/state';
import type { EditorView } from '@tiptap/pm/view';
import type { Editor } from '@tiptap/react';
import type { ArticleImageAttributes } from '@/lib/tiptap/article-image';
import type { ArticleMedia } from '@/types';

export function getArticleImagePosFromFigure(
    view: EditorView,
    figure: Element,
): number | null {
    const pos = view.posAtDOM(figure, 0);
    const node = view.state.doc.nodeAt(pos);

    if (node?.type.name === 'articleImage') {
        return pos;
    }

    const $pos = view.state.doc.resolve(pos);

    if ($pos.parent.type.name === 'articleImage') {
        return $pos.before($pos.depth);
    }

    return null;
}

export function selectArticleImageFigure(
    view: EditorView,
    figure: Element,
): string | null {
    const pos = getArticleImagePosFromFigure(view, figure);

    if (pos === null) {
        return null;
    }

    const node = view.state.doc.nodeAt(pos);

    if (node?.type.name !== 'articleImage') {
        return null;
    }

    view.dispatch(
        view.state.tr.setSelection(NodeSelection.create(view.state.doc, pos)),
    );
    view.focus();

    return node.attrs.mediaId as string;
}

export function getSelectedArticleImage(
    editor: Editor,
): ArticleImageAttributes | null {
    const { selection } = editor.state;

    if (
        selection instanceof NodeSelection &&
        selection.node.type.name === 'articleImage'
    ) {
        return selection.node.attrs as ArticleImageAttributes;
    }

    return null;
}

export function deleteSelectedArticleImage(editor: Editor): boolean {
    if (!getSelectedArticleImage(editor)) {
        return false;
    }

    return editor.chain().focus().deleteSelection().run();
}

export function insertArticleImage(
    editor: Editor,
    media: ArticleMedia,
): void {
    editor
        .chain()
        .focus()
        .setArticleImage({
            mediaId: media.id,
            alt: media.alt_text,
            copyright: media.copyright,
            caption: media.caption,
            previewWebpUrl: media.preview_webp_url,
            previewJpegUrl: media.preview_jpeg_url,
        })
        .run();
}

export function syncArticleImagesFromMedia(
    editor: Editor,
    mediaItems: ArticleMedia[],
): void {
    const mediaById = new Map(mediaItems.map((item) => [item.id, item]));

    editor.state.doc.descendants((node, pos) => {
        if (node.type.name !== 'articleImage') {
            return;
        }

        const media = mediaById.get(node.attrs.mediaId as string);

        if (!media) {
            return;
        }

        const needsUpdate =
            node.attrs.alt !== media.alt_text ||
            node.attrs.copyright !== media.copyright ||
            node.attrs.caption !== media.caption ||
            node.attrs.previewWebpUrl !== media.preview_webp_url ||
            node.attrs.previewJpegUrl !== media.preview_jpeg_url;

        if (needsUpdate) {
            editor.commands.updateArticleImageById(media.id, {
                alt: media.alt_text,
                copyright: media.copyright,
                caption: media.caption,
                previewWebpUrl: media.preview_webp_url,
                previewJpegUrl: media.preview_jpeg_url,
            });
        }
    });
}

export function getArticleImageMediaIdsFromEditor(editor: Editor): string[] {
    const ids: string[] = [];

    editor.state.doc.descendants((node) => {
        if (node.type.name === 'articleImage' && node.attrs.mediaId) {
            ids.push(node.attrs.mediaId as string);
        }
    });

    return ids;
}
