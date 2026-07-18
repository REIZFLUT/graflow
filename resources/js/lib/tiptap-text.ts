import type { TipTapDocument, TipTapNode } from '@/types';

const BLOCK_TYPES = new Set([
    'paragraph',
    'heading',
    'blockquote',
    'listItem',
    'bulletList',
    'orderedList',
    'codeBlock',
    'tableRow',
    'blockMath',
]);

function appendNodeText(node: TipTapNode, parts: string[]): void {
    if (typeof node.text === 'string') {
        parts.push(node.text);
    }

    if (Array.isArray(node.content)) {
        for (const child of node.content) {
            appendNodeText(child, parts);
        }
    }

    if (node.type && BLOCK_TYPES.has(node.type)) {
        parts.push('\n');
    }
}

function normalizePlainText(text: string): string {
    return text
        .replace(/\n{3,}/g, '\n\n')
        .replace(/[ \t]+\n/g, '\n')
        .trim();
}

/**
 * Extract the plain text of a TipTap document, using newlines to separate
 * block-level nodes. Formatting marks are intentionally ignored.
 */
export function tiptapToPlainText(doc: TipTapDocument | null): string {
    if (!doc) {
        return '';
    }

    const parts: string[] = [];
    appendNodeText(doc, parts);

    return normalizePlainText(parts.join(''));
}

/**
 * Same block boundaries as {@link tiptapToPlainText}, returned as individual lines.
 */
export function tiptapToPlainTextLines(doc: TipTapDocument | null): string[] {
    const text = tiptapToPlainText(doc);

    if (text === '') {
        return [];
    }

    return text.split('\n');
}

export function segmentsToPlainText(
    segments: Array<{ value: string }> | null,
): string {
    if (!segments) {
        return '';
    }

    return segments
        .map((segment) => segment.value)
        .join('')
        .trim();
}
