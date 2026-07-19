import { Extension } from '@tiptap/core';
import type { MarkType, Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorState, Transaction } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';

export type CommentHighlightState = {
    activeThreadId: string | null;
    resolvedThreadIds: string[];
    visible: boolean;
};

type CommentHighlightPluginState = CommentHighlightState & {
    decorations: DecorationSet;
};

type CommentHighlightMeta = {
    type: 'setState';
    state: CommentHighlightState;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        commentHighlight: {
            setCommentHighlightState: (
                state: CommentHighlightState,
            ) => ReturnType;
        };
    }
}

export const commentHighlightPluginKey =
    new PluginKey<CommentHighlightPluginState>('commentHighlight');

function emptyState(): CommentHighlightPluginState {
    return {
        activeThreadId: null,
        resolvedThreadIds: [],
        visible: true,
        decorations: DecorationSet.empty,
    };
}

function createDecorations(
    doc: ProseMirrorNode,
    markType: MarkType | undefined,
    state: CommentHighlightState,
): DecorationSet {
    if (!markType) {
        return DecorationSet.empty;
    }

    const decorations: Decoration[] = [];
    const resolved = new Set(state.resolvedThreadIds);

    doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const commentMark = node.marks.find((mark) => mark.type === markType);
        const threadId = commentMark?.attrs.threadId as string | undefined;

        if (!threadId) {
            return;
        }

        if (!state.visible) {
            return;
        }

        const classes = ['article-comment-mark-decoration'];

        if (resolved.has(threadId)) {
            classes.push('article-comment-mark--resolved');
        } else if (state.activeThreadId === threadId) {
            classes.push('article-comment-mark--active');
        } else {
            classes.push('article-comment-mark--visible');
        }

        decorations.push(
            Decoration.inline(pos, pos + node.nodeSize, {
                class: classes.join(' '),
            }),
        );
    });

    return DecorationSet.create(doc, decorations);
}

function getPluginState(state: EditorState): CommentHighlightPluginState {
    return commentHighlightPluginKey.getState(state) ?? emptyState();
}

export const CommentHighlight = Extension.create({
    name: 'commentHighlight',

    addCommands() {
        return {
            setCommentHighlightState:
                (state: CommentHighlightState) =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(commentHighlightPluginKey, {
                            type: 'setState',
                            state,
                        } satisfies CommentHighlightMeta);
                        dispatch(tr);
                    }

                    return true;
                },
        };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin<CommentHighlightPluginState>({
                key: commentHighlightPluginKey,
                state: {
                    init: () => emptyState(),
                    apply: (
                        tr: Transaction,
                        value: CommentHighlightPluginState,
                    ): CommentHighlightPluginState => {
                        const markType = tr.doc.type.schema.marks.comment;
                        const meta = tr.getMeta(commentHighlightPluginKey) as
                            | CommentHighlightMeta
                            | undefined;

                        if (meta) {
                            return {
                                ...meta.state,
                                decorations: createDecorations(
                                    tr.doc,
                                    markType,
                                    meta.state,
                                ),
                            };
                        }

                        if (!tr.docChanged) {
                            return value;
                        }

                        return {
                            ...value,
                            decorations: createDecorations(
                                tr.doc,
                                markType,
                                value,
                            ),
                        };
                    },
                },
                props: {
                    decorations(state) {
                        return getPluginState(state).decorations;
                    },
                },
            }),
        ];
    },
});
