import { Mark, mergeAttributes } from '@tiptap/core';
import type { Mark as ProseMirrorMark } from '@tiptap/pm/model';
import type { EditorState, Transaction } from '@tiptap/pm/state';

export type CommentMarkAttributes = {
    threadId: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        comment: {
            setCommentThread: (threadId: string) => ReturnType;
            removeCommentThreadById: (threadId: string) => ReturnType;
        };
    }
}

function trimSelectionBounds(
    state: EditorState,
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

function removeCommentThreadFromDocument(
    state: EditorState,
    tr: Transaction,
    threadId: string,
): boolean {
    const markType = state.schema.marks.comment;
    const ranges: Array<{
        from: number;
        to: number;
        mark: ProseMirrorMark;
    }> = [];

    state.doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const commentMark = node.marks.find(
            (mark) =>
                mark.type === markType &&
                (mark.attrs.threadId as string) === threadId,
        );

        if (!commentMark) {
            return;
        }

        ranges.push({
            from: pos,
            to: pos + node.nodeSize,
            mark: commentMark,
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

export const CommentMark = Mark.create({
    name: 'comment',

    inclusive: false,

    excludes: '',

    addAttributes() {
        return {
            threadId: {
                default: null,
                parseHTML: (element) =>
                    element.getAttribute('data-comment-thread-id'),
                renderHTML: (attributes) => ({
                    'data-comment-thread-id': attributes.threadId,
                }),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-comment-thread-id]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                class: 'article-comment-mark',
            }),
            0,
        ];
    },

    addCommands() {
        return {
            setCommentThread:
                (threadId: string) =>
                ({ chain, state }) => {
                    const { from, to, empty } = state.selection;

                    if (empty || threadId.trim() === '') {
                        return false;
                    }

                    const bounds = trimSelectionBounds(state, from, to);

                    if (!bounds) {
                        return false;
                    }

                    return chain()
                        .setTextSelection(bounds)
                        .setMark(this.name, { threadId })
                        .run();
                },
            removeCommentThreadById:
                (threadId: string) =>
                ({ state, dispatch }) => {
                    const { tr } = state;

                    if (!removeCommentThreadFromDocument(state, tr, threadId)) {
                        return false;
                    }

                    dispatch?.(tr);

                    return true;
                },
        };
    },
});
