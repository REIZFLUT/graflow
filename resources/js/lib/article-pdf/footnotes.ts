import type { JSONContent } from '@tiptap/core';

export type CollectedFootnote = {
    id: string;
    content: string;
    index: number;
};

export function collectFootnotes(content: JSONContent): CollectedFootnote[] {
    const footnotes = new Map<string, string>();

    walk(content, footnotes);

    return [...footnotes.entries()].map(([id, text], index) => ({
        id,
        content: text,
        index: index + 1,
    }));
}

function walk(node: JSONContent, footnotes: Map<string, string>): void {
    if (Array.isArray(node.marks)) {
        for (const mark of node.marks) {
            if (mark.type === 'footnote' && mark.attrs?.id) {
                footnotes.set(
                    String(mark.attrs.id),
                    String(mark.attrs.content ?? ''),
                );
            }
        }
    }

    if (Array.isArray(node.content)) {
        for (const child of node.content) {
            walk(child, footnotes);
        }
    }
}

export function footnoteIndexForId(
    footnotes: CollectedFootnote[],
    id: string | null | undefined,
): number | null {
    if (!id) {
        return null;
    }

    const footnote = footnotes.find((entry) => entry.id === id);

    return footnote?.index ?? null;
}
