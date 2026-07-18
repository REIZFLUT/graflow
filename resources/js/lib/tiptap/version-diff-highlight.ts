import { Extension } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorState, Transaction } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import type { Editor } from '@tiptap/react';

type VersionDiffHighlightRange = {
    from: number;
    to: number;
};

type VersionDiffHighlightPluginState = {
    decorations: DecorationSet;
    range: VersionDiffHighlightRange | null;
};

type VersionDiffHighlightMeta =
    { type: 'set'; range: VersionDiffHighlightRange } | { type: 'clear' };

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        versionDiffHighlight: {
            highlightVersionDiffBlock: (pos: number) => ReturnType;
            clearVersionDiffHighlight: () => ReturnType;
        };
    }
}

export const versionDiffHighlightPluginKey =
    new PluginKey<VersionDiffHighlightPluginState>('versionDiffHighlight');

let clearHighlightTimeout: ReturnType<typeof setTimeout> | null = null;

function emptyState(): VersionDiffHighlightPluginState {
    return {
        decorations: DecorationSet.empty,
        range: null,
    };
}

function createDecoration(
    doc: ProseMirrorNode,
    range: VersionDiffHighlightRange,
): DecorationSet {
    return DecorationSet.create(doc, [
        Decoration.node(range.from, range.to, {
            class: 'version-diff-highlight',
        }),
    ]);
}

function getPluginState(state: EditorState): VersionDiffHighlightPluginState {
    return versionDiffHighlightPluginKey.getState(state) ?? emptyState();
}

function applyMeta(
    meta: VersionDiffHighlightMeta,
    doc: ProseMirrorNode,
): VersionDiffHighlightPluginState {
    if (meta.type === 'clear') {
        return emptyState();
    }

    return {
        range: meta.range,
        decorations: createDecoration(doc, meta.range),
    };
}

function scheduleClear(editor: Editor, durationMs: number): void {
    if (clearHighlightTimeout) {
        clearTimeout(clearHighlightTimeout);
    }

    clearHighlightTimeout = setTimeout(() => {
        editor.commands.clearVersionDiffHighlight();
        clearHighlightTimeout = null;
    }, durationMs);
}

export const VersionDiffHighlight = Extension.create({
    name: 'versionDiffHighlight',

    addCommands() {
        return {
            highlightVersionDiffBlock:
                (pos) =>
                ({ editor, tr, dispatch }) => {
                    const node = editor.state.doc.nodeAt(pos);

                    if (!node) {
                        return false;
                    }

                    const range = {
                        from: pos,
                        to: pos + node.nodeSize,
                    };

                    if (dispatch) {
                        tr.setMeta(versionDiffHighlightPluginKey, {
                            type: 'set',
                            range,
                        } satisfies VersionDiffHighlightMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            clearVersionDiffHighlight:
                () =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(versionDiffHighlightPluginKey, {
                            type: 'clear',
                        } satisfies VersionDiffHighlightMeta);
                        dispatch(tr);
                    }

                    return true;
                },
        };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin<VersionDiffHighlightPluginState>({
                key: versionDiffHighlightPluginKey,
                state: {
                    init: () => emptyState(),
                    apply: (
                        tr: Transaction,
                        value: VersionDiffHighlightPluginState,
                    ): VersionDiffHighlightPluginState => {
                        const meta = tr.getMeta(
                            versionDiffHighlightPluginKey,
                        ) as VersionDiffHighlightMeta | undefined;

                        if (meta) {
                            return applyMeta(meta, tr.doc);
                        }

                        if (!tr.docChanged) {
                            return value;
                        }

                        if (!value.range) {
                            return value;
                        }

                        const mappedFrom = tr.mapping.map(value.range.from);
                        const mappedTo = tr.mapping.map(value.range.to);

                        if (mappedFrom === mappedTo) {
                            return emptyState();
                        }

                        const range = {
                            from: mappedFrom,
                            to: mappedTo,
                        };

                        return {
                            range,
                            decorations: createDecoration(tr.doc, range),
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

export function highlightEditorBlockAtPos(
    editor: Editor,
    pos: number,
    durationMs = 3000,
): boolean {
    const node = editor.state.doc.nodeAt(pos);

    if (!node) {
        return false;
    }

    editor
        .chain()
        .focus()
        .highlightVersionDiffBlock(pos)
        .setTextSelection(pos + 1)
        .scrollIntoView()
        .run();

    scheduleClear(editor, durationMs);

    return true;
}

export function clearEditorBlockHighlight(editor: Editor): void {
    if (clearHighlightTimeout) {
        clearTimeout(clearHighlightTimeout);
        clearHighlightTimeout = null;
    }

    editor.commands.clearVersionDiffHighlight();
}
