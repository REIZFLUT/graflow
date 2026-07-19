import { Extension } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorState, Transaction } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import type { Editor } from '@tiptap/react';
import type { MappedSpellCheckMatch } from '@/lib/tiptap/spellcheck-utils';

export type { MappedSpellCheckMatch } from '@/lib/tiptap/spellcheck-utils';

type SpellCheckPluginState = {
    decorations: DecorationSet;
    matches: MappedSpellCheckMatch[];
    highlightedMatchId: string | null;
};

type SpellCheckMeta =
    | { type: 'setMatches'; matches: MappedSpellCheckMatch[] }
    | { type: 'clear' }
    | { type: 'dismiss'; id: string }
    | { type: 'highlight'; id: string }
    | { type: 'clearHighlight' };

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        spellCheck: {
            setSpellCheckMatches: (
                matches: MappedSpellCheckMatch[],
            ) => ReturnType;
            clearSpellCheck: () => ReturnType;
            applySpellCheckReplacement: (
                id: string,
                value: string,
            ) => ReturnType;
            dismissSpellCheckMatch: (id: string) => ReturnType;
            highlightSpellCheckMatch: (id: string) => ReturnType;
            clearSpellCheckHighlight: () => ReturnType;
        };
    }
}

export const spellCheckPluginKey = new PluginKey<SpellCheckPluginState>(
    'spellCheck',
);

function createDecorations(
    doc: ProseMirrorNode,
    matches: MappedSpellCheckMatch[],
    highlightedMatchId: string | null = null,
): DecorationSet {
    return DecorationSet.create(
        doc,
        matches.map((match) => {
            const classes = [
                'spellcheck-error',
                `spellcheck-${match.categoryClass}`,
                ...(highlightedMatchId === match.id
                    ? ['spellcheck-error-highlight']
                    : []),
            ].join(' ');

            return Decoration.inline(
                match.from,
                match.to,
                {
                    class: classes,
                    'data-spellcheck-id': match.id,
                },
                {
                    id: match.id,
                    match,
                },
            );
        }),
    );
}

function matchesFromDecorations(
    decorations: DecorationSet,
): MappedSpellCheckMatch[] {
    return decorations.find().map((decoration) => {
        const match = decoration.spec.match as MappedSpellCheckMatch;

        return {
            ...match,
            from: decoration.from,
            to: decoration.to,
        };
    });
}

function emptyState(): SpellCheckPluginState {
    return {
        decorations: DecorationSet.empty,
        matches: [],
        highlightedMatchId: null,
    };
}

function applyMeta(
    meta: SpellCheckMeta,
    value: SpellCheckPluginState,
    doc: ProseMirrorNode,
): SpellCheckPluginState {
    if (meta.type === 'setMatches') {
        return {
            matches: meta.matches,
            highlightedMatchId: null,
            decorations: createDecorations(doc, meta.matches),
        };
    }

    if (meta.type === 'clear') {
        return emptyState();
    }

    if (meta.type === 'highlight') {
        return {
            ...value,
            highlightedMatchId: meta.id,
            decorations: createDecorations(doc, value.matches, meta.id),
        };
    }

    if (meta.type === 'clearHighlight') {
        return {
            ...value,
            highlightedMatchId: null,
            decorations: createDecorations(doc, value.matches),
        };
    }

    const matches = value.matches.filter((match) => match.id !== meta.id);
    const highlightedMatchId =
        value.highlightedMatchId === meta.id ? null : value.highlightedMatchId;

    return {
        matches,
        highlightedMatchId,
        decorations: createDecorations(doc, matches, highlightedMatchId),
    };
}

function getPluginState(state: EditorState): SpellCheckPluginState {
    return spellCheckPluginKey.getState(state) ?? emptyState();
}

export function getSpellCheckMatches(editor: Editor | null): MappedSpellCheckMatch[] {
    if (!editor) {
        return [];
    }

    return getPluginState(editor.state).matches;
}

export function getSpellCheckMatchById(
    editor: Editor | null,
    id: string,
): MappedSpellCheckMatch | null {
    return getSpellCheckMatches(editor).find((match) => match.id === id) ?? null;
}

export function focusSpellCheckMatch(
    editor: Editor,
    match: MappedSpellCheckMatch,
): void {
    editor
        .chain()
        .focus()
        .setTextSelection({ from: match.from, to: match.to })
        .run();

    highlightSpellCheckMatch(editor, match.id);

    const dom = editor.view.dom.querySelector<HTMLElement>(
        `[data-spellcheck-id="${CSS.escape(match.id)}"]`,
    );

    dom?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

let highlightTimeoutId: ReturnType<typeof setTimeout> | null = null;

export function highlightSpellCheckMatch(editor: Editor, matchId: string): void {
    editor.commands.highlightSpellCheckMatch(matchId);

    if (highlightTimeoutId !== null) {
        clearTimeout(highlightTimeoutId);
    }

    highlightTimeoutId = setTimeout(() => {
        editor.commands.clearSpellCheckHighlight();
        highlightTimeoutId = null;
    }, 3000);
}

export const SpellCheck = Extension.create({
    name: 'spellCheck',

    addCommands() {
        return {
            setSpellCheckMatches:
                (matches) =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'setMatches',
                            matches,
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            clearSpellCheck:
                () =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'clear',
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            applySpellCheckReplacement:
                (id, value) =>
                ({ editor, tr, dispatch }) => {
                    const match = getPluginState(editor.state).matches.find(
                        (entry) => entry.id === id,
                    );

                    if (!match) {
                        return false;
                    }

                    if (dispatch) {
                        tr.insertText(value, match.from, match.to);
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'dismiss',
                            id,
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            dismissSpellCheckMatch:
                (id) =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'dismiss',
                            id,
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            highlightSpellCheckMatch:
                (id) =>
                ({ editor, tr, dispatch }) => {
                    const match = getPluginState(editor.state).matches.find(
                        (entry) => entry.id === id,
                    );

                    if (!match) {
                        return false;
                    }

                    if (dispatch) {
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'highlight',
                            id,
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            clearSpellCheckHighlight:
                () =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(spellCheckPluginKey, {
                            type: 'clearHighlight',
                        } satisfies SpellCheckMeta);
                        dispatch(tr);
                    }

                    return true;
                },
        };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin<SpellCheckPluginState>({
                key: spellCheckPluginKey,
                state: {
                    init: () => emptyState(),
                    apply: (
                        tr: Transaction,
                        value: SpellCheckPluginState,
                    ): SpellCheckPluginState => {
                        const meta = tr.getMeta(
                            spellCheckPluginKey,
                        ) as SpellCheckMeta | undefined;

                        if (meta) {
                            if (meta.type === 'dismiss' && tr.docChanged) {
                                const mappedDecorations = value.decorations.map(
                                    tr.mapping,
                                    tr.doc,
                                );
                                const mappedMatches = matchesFromDecorations(
                                    mappedDecorations,
                                ).filter((match) => match.id !== meta.id);
                                const highlightedMatchId =
                                    value.highlightedMatchId === meta.id
                                        ? null
                                        : value.highlightedMatchId;

                                return {
                                    matches: mappedMatches,
                                    highlightedMatchId,
                                    decorations: createDecorations(
                                        tr.doc,
                                        mappedMatches,
                                        highlightedMatchId,
                                    ),
                                };
                            }

                            return applyMeta(meta, value, tr.doc);
                        }

                        if (!tr.docChanged) {
                            return value;
                        }

                        const decorations = value.decorations.map(
                            tr.mapping,
                            tr.doc,
                        );
                        const matches = matchesFromDecorations(decorations);
                        const highlightedMatchId =
                            value.highlightedMatchId &&
                            matches.some(
                                (match) =>
                                    match.id === value.highlightedMatchId,
                            )
                                ? value.highlightedMatchId
                                : null;

                        return {
                            decorations,
                            matches,
                            highlightedMatchId,
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
