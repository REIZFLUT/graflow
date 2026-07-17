import type { JSONContent } from '@tiptap/core';
import { latexToImageDataUrl } from '@/lib/article-pdf/latex-image';

export type PreparedTipTapNode = JSONContent;

export async function prepareContentForPdf(
    content: JSONContent,
): Promise<JSONContent> {
    const clone = structuredClone(content) as JSONContent;

    await walkAndPrepare(clone);

    return clone;
}

async function walkAndPrepare(node: JSONContent): Promise<void> {
    if (node.type === 'inlineMath' && node.attrs?.latex) {
        const image = await latexToImageDataUrl(String(node.attrs.latex), false);

        node.type = 'pdfMathImage';
        node.attrs = {
            src: image.src,
            width: image.width,
            height: image.height,
            displayMode: false,
        };

        return;
    }

    if (node.type === 'blockMath' && node.attrs?.latex) {
        const image = await latexToImageDataUrl(String(node.attrs.latex), true);

        node.type = 'pdfMathImage';
        node.attrs = {
            src: image.src,
            width: image.width,
            height: image.height,
            displayMode: true,
        };

        return;
    }

    if (Array.isArray(node.content)) {
        for (const child of node.content) {
            await walkAndPrepare(child);
        }
    }
}
