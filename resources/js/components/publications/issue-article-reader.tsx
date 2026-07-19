import type { Editor } from '@tiptap/react';
import { useEffect, useState } from 'react';
import FootnotesPanel from '@/components/articles/footnotes-panel';
import MarginalNotesColumn from '@/components/articles/marginal-notes-column';
import TipTapEditor from '@/components/articles/tiptap-editor';
import { useTranslation } from '@/hooks/use-translation';
import {
    getFootnotesFromEditor,
    syncArticleImagesFromMedia,
} from '@/lib/tiptap';
import { cn } from '@/lib/utils';
import type {
    ArticleMedia,
    PublicationEditorSettings,
    TipTapDocument,
} from '@/types';
import { emptyTipTapDocument } from '@/types';

type IssueArticleReaderProps = {
    title: string;
    content: TipTapDocument | null;
    editorSettings: PublicationEditorSettings;
    mediaItems?: ArticleMedia[];
};

export default function IssueArticleReader({
    title,
    content,
    editorSettings,
    mediaItems = [],
}: IssueArticleReaderProps) {
    const { t } = useTranslation();
    const [editor, setEditor] = useState<Editor | null>(null);
    const [footnoteCount, setFootnoteCount] = useState(0);
    const documentContent = content ?? emptyTipTapDocument();

    useEffect(() => {
        if (!editor || mediaItems.length === 0) {
            return;
        }

        syncArticleImagesFromMedia(editor, mediaItems);
    }, [editor, mediaItems]);

    useEffect(() => {
        if (!editor) {
            setFootnoteCount(0);

            return;
        }

        const syncFootnotes = () => {
            setFootnoteCount(getFootnotesFromEditor(editor).length);
        };

        syncFootnotes();
        editor.on('update', syncFootnotes);

        return () => {
            editor.off('update', syncFootnotes);
        };
    }, [editor]);

    return (
        <article
            className={cn(
                'document-page mx-auto max-w-5xl rounded-sm bg-card px-8 py-12 shadow-md ring-1 ring-border/40 md:px-14 md:py-16',
                editorSettings.font === 'roboto'
                    ? 'document-font-roboto'
                    : 'document-font-spectral',
                editorSettings.has_marginal_column && 'document-with-margin',
            )}
        >
            <h2 className="mb-8 text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                {title}
            </h2>

            <div
                className={cn(
                    'grid grid-cols-1',
                    editorSettings.has_marginal_column &&
                        'lg:grid-cols-[minmax(0,1fr)_12rem] lg:gap-8',
                )}
            >
                <TipTapEditor
                    variant="document"
                    content={documentContent}
                    onChange={() => {}}
                    readOnly
                    onEditorReady={setEditor}
                />
                {editor && editorSettings.has_marginal_column && (
                    <MarginalNotesColumn editor={editor} readOnly />
                )}
            </div>

            {editor && footnoteCount > 0 && (
                <div className="mt-12 border-t border-border/60 pt-8">
                    <h3 className="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        {t('articles.editor.footnotes')}
                    </h3>
                    <FootnotesPanel
                        editor={editor}
                        onEditFootnote={() => {}}
                        onRemoveFootnote={() => {}}
                        canEdit={false}
                    />
                </div>
            )}
        </article>
    );
}
