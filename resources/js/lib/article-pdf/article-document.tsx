import { Document, Page, Text, View } from '@react-pdf/renderer';
import type { JSONContent } from '@tiptap/core';
import { collectFootnotes } from '@/lib/article-pdf/footnotes';
import { renderTipTapNode } from '@/lib/article-pdf/render-content';
import { createArticlePdfStyles } from '@/lib/article-pdf/styles';
import type {
    ArticleMedia,
    PublicationEditorSettings,
    TipTapDocument,
} from '@/types';

type ArticlePdfDocumentProps = {
    title: string;
    content: TipTapDocument;
    editorSettings: PublicationEditorSettings;
    mediaItems: ArticleMedia[];
    footnotesTitle: string;
};

export function ArticlePdfDocument({
    title,
    content,
    editorSettings,
    mediaItems,
    footnotesTitle,
}: ArticlePdfDocumentProps) {
    const styles = createArticlePdfStyles(editorSettings);
    const footnotes = collectFootnotes(content);
    const mediaById = new Map(mediaItems.map((media) => [media.id, media]));
    const preparedContent = content as JSONContent;

    return (
        <Document>
            <Page size="A4" style={styles.page}>
                <Text style={styles.title}>{title}</Text>
                {renderTipTapNode(preparedContent, {
                    styles,
                    mediaById,
                    footnotes,
                    hasMarginalColumn: editorSettings.has_marginal_column,
                }, 'doc')}
                {footnotes.length > 0 ? (
                    <View style={styles.footnotesSection}>
                        <Text style={styles.footnotesTitle}>
                            {footnotesTitle}
                        </Text>
                        {footnotes.map((footnote) => (
                            <Text
                                key={footnote.id}
                                style={styles.footnoteItem}
                            >
                                {`${footnote.index}. ${footnote.content}`}
                            </Text>
                        ))}
                    </View>
                ) : null}
            </Page>
        </Document>
    );
}
