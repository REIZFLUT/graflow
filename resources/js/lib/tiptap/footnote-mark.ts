import { Mark, mergeAttributes } from '@tiptap/core';

export type FootnoteMarkAttributes = {
    id: string;
    content: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        footnote: {
            setFootnote: (content: string) => ReturnType;
            updateFootnoteContent: (content: string) => ReturnType;
            updateFootnoteById: (id: string, content: string) => ReturnType;
            removeFootnoteAtSelection: () => ReturnType;
            removeFootnoteById: (id: string) => ReturnType;
        };
    }
}

function findFootnoteMarkAtSelection(
    state: import('@tiptap/pm/state').EditorState,
): { id: string; content: string } | null {
    const { from, to, empty } = state.selection;

    if (empty) {
        return null;
    }

    const markType = state.schema.marks.footnote;
    let found: { id: string; content: string } | null = null;

    state.doc.nodesBetween(from, to, (node) => {
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

function updateFootnoteById(
    state: import('@tiptap/pm/state').EditorState,
    tr: import('@tiptap/pm/state').Transaction,
    id: string,
    content: string,
): void {
    const markType = state.schema.marks.footnote;

    state.doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const footnoteMark = node.marks.find(
            (mark) =>
                mark.type === markType && (mark.attrs.id as string) === id,
        );

        if (!footnoteMark) {
            return;
        }

        tr.removeMark(pos, pos + node.nodeSize, markType);
        tr.addMark(
            pos,
            pos + node.nodeSize,
            markType.create({
                id,
                content,
            }),
        );
    });
}

function trimSelectionBounds(
    state: import('@tiptap/pm/state').EditorState,
    from: number,
    to: number,
): { from: number; to: number } | null {
    if (from >= to) {
        return null;
    }

    const text = state.doc.textBetween(from, to, '\n', '\n');
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

function removeFootnoteByIdFromDocument(
    state: import('@tiptap/pm/state').EditorState,
    tr: import('@tiptap/pm/state').Transaction,
    id: string,
): boolean {
    const markType = state.schema.marks.footnote;
    const ranges: Array<{ from: number; to: number; mark: import('@tiptap/pm/model').Mark }> = [];

    state.doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const footnoteMark = node.marks.find(
            (mark) =>
                mark.type === markType && (mark.attrs.id as string) === id,
        );

        if (!footnoteMark) {
            return;
        }

        ranges.push({
            from: pos,
            to: pos + node.nodeSize,
            mark: footnoteMark,
        });
    });

    if (ranges.length === 0) {
        return false;
    }

    for (const range of ranges.sort((left, right) => right.from - left.from)) {
        tr.removeMark(range.from, range.to, range.mark);
    }

    return true;
}

export const FootnoteMark = Mark.create({
    name: 'footnote',

    inclusive: false,

    addAttributes() {
        return {
            id: {
                default: null,
                parseHTML: (element) =>
                    element.getAttribute('data-footnote-id'),
                renderHTML: (attributes) => ({
                    'data-footnote-id': attributes.id,
                }),
            },
            content: {
                default: '',
                parseHTML: (element) =>
                    element.getAttribute('data-footnote-content') ?? '',
                renderHTML: (attributes) => ({
                    'data-footnote-content': attributes.content,
                }),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-footnote-id]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                class: 'article-footnote-mark',
            }),
            0,
        ];
    },

    addCommands() {
        return {
            setFootnote:
                (content: string) =>
                ({ chain, state }) => {
                    const { from, to, empty } = state.selection;

                    if (empty || content.trim() === '') {
                        return false;
                    }

                    const bounds = trimSelectionBounds(state, from, to);

                    if (!bounds) {
                        return false;
                    }

                    return chain()
                        .setTextSelection(bounds)
                        .setMark(this.name, {
                            id: crypto.randomUUID(),
                            content: content.trim(),
                        })
                        .run();
                },
            updateFootnoteContent:
                (content: string) =>
                ({ state, dispatch }) => {
                    const footnote = findFootnoteMarkAtSelection(state);

                    if (!footnote) {
                        return false;
                    }

                    if (content.trim() === '') {
                        const { tr } = state;

                        if (
                            !removeFootnoteByIdFromDocument(
                                state,
                                tr,
                                footnote.id,
                            )
                        ) {
                            return false;
                        }

                        dispatch?.(tr);

                        return true;
                    }

                    const { tr } = state;
                    updateFootnoteById(
                        state,
                        tr,
                        footnote.id,
                        content.trim(),
                    );
                    dispatch?.(tr);

                    return true;
                },
            updateFootnoteById:
                (id: string, content: string) =>
                ({ state, dispatch }) => {
                    if (content.trim() === '') {
                        const { tr } = state;

                        if (
                            !removeFootnoteByIdFromDocument(state, tr, id)
                        ) {
                            return false;
                        }

                        dispatch?.(tr);

                        return true;
                    }

                    const { tr } = state;
                    updateFootnoteById(state, tr, id, content.trim());
                    dispatch?.(tr);

                    return true;
                },
            removeFootnoteAtSelection:
                () =>
                ({ state, dispatch }) => {
                    const footnote = findFootnoteMarkAtSelection(state);

                    if (!footnote) {
                        return false;
                    }

                    const { tr } = state;

                    if (
                        !removeFootnoteByIdFromDocument(
                            state,
                            tr,
                            footnote.id,
                        )
                    ) {
                        return false;
                    }

                    dispatch?.(tr);

                    return true;
                },
            removeFootnoteById:
                (id: string) =>
                ({ state, dispatch }) => {
                    const { tr } = state;

                    if (!removeFootnoteByIdFromDocument(state, tr, id)) {
                        return false;
                    }

                    dispatch?.(tr);

                    return true;
                },
        };
    },
});
