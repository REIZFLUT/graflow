import { StyleSheet } from '@react-pdf/renderer';
import type { PublicationEditorSettings } from '@/types';

export function createArticlePdfStyles(
    editorSettings: PublicationEditorSettings,
) {
    const fontFamily =
        editorSettings.font === 'roboto' ? 'Roboto' : 'Spectral';

    return StyleSheet.create({
        page: {
            paddingTop: 42,
            paddingBottom: 42,
            paddingHorizontal: 42,
            fontFamily,
            fontSize: 11,
            fontWeight: 300,
            lineHeight: 1.75,
            color: '#18181b',
        },
        title: {
            fontSize: 24,
            fontWeight: 700,
            letterSpacing: -0.5,
            lineHeight: 1.2,
            marginBottom: 24,
        },
        h2: {
            fontSize: 19,
            fontWeight: 700,
            letterSpacing: -0.4,
            lineHeight: 1.25,
            marginTop: 20,
            marginBottom: 8,
        },
        h3: {
            fontSize: 13,
            fontWeight: 600,
            letterSpacing: -0.2,
            lineHeight: 1.35,
            marginTop: 16,
            marginBottom: 6,
        },
        paragraph: {
            marginTop: 6,
            marginBottom: 6,
        },
        paragraphCompact: {
            marginTop: 0,
            marginBottom: 0,
        },
        marginalNote: {
            fontSize: 10,
            fontWeight: 600,
            lineHeight: 1.45,
            color: '#52525b',
        },
        marginalRow: {
            flexDirection: 'row',
            alignItems: 'flex-start',
            gap: 24,
        },
        marginalMain: {
            flex: 1,
        },
        marginalColumn: {
            width: 108,
            borderLeftWidth: 1,
            borderLeftColor: '#d4d4d8',
            paddingLeft: 12,
        },
        blockquote: {
            borderLeftWidth: 3,
            borderLeftColor: '#d4d4d8',
            paddingLeft: 12,
            color: '#71717a',
            marginVertical: 8,
        },
        infoBox: {
            backgroundColor: '#f4f4f5',
            borderLeftWidth: 4,
            borderLeftColor: '#18181b',
            paddingVertical: 12,
            paddingHorizontal: 14,
            marginVertical: 10,
        },
        list: {
            marginVertical: 6,
            paddingLeft: 0,
        },
        listItem: {
            flexDirection: 'row',
            alignItems: 'flex-start',
            marginVertical: 2,
        },
        listMarker: {
            width: 18,
            paddingRight: 4,
        },
        listItemContent: {
            flex: 1,
        },
        image: {
            marginVertical: 12,
        },
        imageAsset: {
            maxWidth: '100%',
        },
        imageCaption: {
            fontSize: 9,
            color: '#71717a',
            marginTop: 6,
        },
        imageCopyright: {
            fontSize: 8,
            color: '#71717a',
            marginTop: 3,
        },
        mathBlock: {
            marginVertical: 8,
            alignItems: 'center',
        },
        mathInline: {
            marginHorizontal: 1,
            marginTop: -1,
        },
        table: {
            marginVertical: 10,
            borderWidth: 1,
            borderColor: '#d4d4d8',
        },
        tableRow: {
            flexDirection: 'row',
        },
        tableCell: {
            flex: 1,
            borderRightWidth: 1,
            borderBottomWidth: 1,
            borderColor: '#d4d4d8',
            padding: 6,
        },
        tableHeaderCell: {
            flex: 1,
            borderRightWidth: 1,
            borderBottomWidth: 1,
            borderColor: '#d4d4d8',
            padding: 6,
            backgroundColor: '#f4f4f5',
            fontWeight: 600,
        },
        footnotesSection: {
            marginTop: 28,
            paddingTop: 16,
            borderTopWidth: 1,
            borderTopColor: '#d4d4d8',
        },
        footnotesTitle: {
            fontSize: 11,
            fontWeight: 600,
            marginBottom: 10,
        },
        footnoteItem: {
            fontSize: 9,
            lineHeight: 1.6,
            marginBottom: 4,
        },
        bold: {
            fontWeight: 700,
        },
        italic: {
            fontStyle: 'italic',
        },
        superscript: {
            fontSize: 8,
            verticalAlign: 'super',
        },
        footnoteMarker: {
            fontSize: 11,
        },
        subscript: {
            fontSize: 8,
            verticalAlign: 'sub',
        },
    });
}

export type ArticlePdfStyles = ReturnType<typeof createArticlePdfStyles>;
