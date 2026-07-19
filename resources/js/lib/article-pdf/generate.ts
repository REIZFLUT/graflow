import { Buffer } from 'buffer';
import { pdf } from '@react-pdf/renderer';
import { createElement } from 'react';
import { ArticlePdfDocument } from '@/lib/article-pdf/article-document';
import {
    registerArticlePdfFonts,
    registerArticlePdfHyphenation,
} from '@/lib/article-pdf/fonts';
import { prepareContentForPdf } from '@/lib/article-pdf/prepare-content';
import type {
    ArticleMedia,
    PublicationEditorSettings,
    TipTapDocument,
} from '@/types';

export type GenerateArticlePdfOptions = {
    title: string;
    content: TipTapDocument;
    editorSettings: PublicationEditorSettings;
    mediaItems: ArticleMedia[];
    locale: string;
    footnotesTitle: string;
};

export async function generateArticlePdfBlob(
    options: GenerateArticlePdfOptions,
): Promise<Blob> {
    if (typeof globalThis.Buffer === 'undefined') {
        globalThis.Buffer = Buffer;
    }

    registerArticlePdfFonts();
    registerArticlePdfHyphenation(options.locale);

    const preparedContent = await prepareContentForPdf(options.content);

    const document = createElement(ArticlePdfDocument, {
        ...options,
        content: preparedContent,
    });

    return pdf(document).toBlob();
}
