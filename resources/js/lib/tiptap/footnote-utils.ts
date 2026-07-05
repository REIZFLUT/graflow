import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import type { Editor } from '@tiptap/react';

export type ArticleFootnote = {
    id: string;
    content: string;
    excerpt: string;
    from: number;
    to: number;
};

export function trimSelectionBounds(
    doc: ProseMirrorNode,
    from: number,
    to: number,
): { from: number; to: number } | null {
    if (from >= to) {
        return null;
    }

    const text = doc.textBetween(from, to, '\n', '\n');
    const leadingWhitespace = text.match(/^\s*/)?.[0].length ?? 0;
    const trailingWhitespace = text.match(/\s*$/)?.[0].length ?? 0;
    const trimmedFrom = from + leadingWhitespace;
    const trimmedTo = to - trailingWhitespace;

    if (trimmedFrom >= trimmedTo) {
        return null;
    }

    return {
        from: trimmedFrom,
        to: trimmedTo,
    };
}

export function getFootnoteAtSelection(
    editor: Editor,
): { id: string; content: string } | null {
    const { from, to, empty } = editor.state.selection;

    if (empty) {
        return null;
    }

    const markType = editor.schema.marks.footnote;
    let found: { id: string; content: string } | null = null;

    editor.state.doc.nodesBetween(from, to, (node) => {
        if (!node.isText) {
            return;
        }

        const footnoteMark = node.marks.find(
            (mark) => mark.type === markType,
        );

        if (footnoteMark?.attrs.id) {
            found = {
                id: footnoteMark.attrs.id as string,
                content: (footnoteMark.attrs.content as string) ?? '',
            };
        }
    });

    return found;
}

export function getFootnotesFromEditor(editor: Editor): ArticleFootnote[] {
    const markType = editor.schema.marks.footnote;
    const grouped = new Map<
        string,
        { content: string; ranges: Array<{ from: number; to: number }> }
    >();

    editor.state.doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const footnoteMark = node.marks.find(
            (mark) => mark.type === markType,
        );

        if (!footnoteMark?.attrs.id) {
            return;
        }

        const id = footnoteMark.attrs.id as string;
        const existing = grouped.get(id) ?? {
            content: (footnoteMark.attrs.content as string) ?? '',
            ranges: [],
        };

        existing.ranges.push({
            from: pos,
            to: pos + node.nodeSize,
        });
        grouped.set(id, existing);
    });

    return Array.from(grouped.entries())
        .map(([id, { content, ranges }]) => {
            const from = Math.min(...ranges.map((range) => range.from));
            const to = Math.max(...ranges.map((range) => range.to));

            return {
                id,
                content,
                excerpt: editor.state.doc.textBetween(from, to),
                from,
                to,
            };
        })
        .sort((left, right) => left.from - right.from);
}

export function removeFootnoteById(editor: Editor, id: string): boolean {
    return editor.commands.removeFootnoteById(id);
}

export function getFootnoteById(
    editor: Editor,
    id: string,
): ArticleFootnote | null {
    return getFootnotesFromEditor(editor).find((footnote) => footnote.id === id) ?? null;
}

export function focusFootnoteInEditor(
    editor: Editor,
    footnote: Pick<ArticleFootnote, 'from' | 'to'>,
): void {
    editor
        .chain()
        .focus()
        .setTextSelection({ from: footnote.from, to: footnote.to })
        .scrollIntoView()
        .run();
}
