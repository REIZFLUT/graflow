import { Extension } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorState, Transaction } from '@tiptap/pm/state';
import type { Mapping } from '@tiptap/pm/transform';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import type { Editor } from '@tiptap/react';
import type { MappedProofreadIssue } from '@/lib/tiptap/proofread-utils';

export type { MappedProofreadIssue } from '@/lib/tiptap/proofread-utils';

type ProofreadPluginState = {
    issues: MappedProofreadIssue[];
    decorations: DecorationSet;
    highlightedIssueId: string | null;
};

type ProofreadMeta =
    | { type: 'setIssues'; issues: MappedProofreadIssue[] }
    | { type: 'clear' }
    | { type: 'dismiss'; id: string }
    | { type: 'highlight'; id: string }
    | { type: 'clearHighlight' };

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        proofread: {
            setProofreadIssues: (
                issues: MappedProofreadIssue[],
            ) => ReturnType;
            clearProofread: () => ReturnType;
            applyProofreadSuggestion: (id: string, value: string) => ReturnType;
            dismissProofreadIssue: (id: string) => ReturnType;
            highlightProofreadIssue: (id: string) => ReturnType;
            clearProofreadHighlight: () => ReturnType;
        };
    }
}

export const proofreadPluginKey = new PluginKey<ProofreadPluginState>(
    'proofread',
);

function isLocated(
    issue: MappedProofreadIssue,
): issue is MappedProofreadIssue & { from: number; to: number } {
    return issue.from !== null && issue.to !== null;
}

function createDecorations(
    doc: ProseMirrorNode,
    issues: MappedProofreadIssue[],
    highlightedIssueId: string | null = null,
): DecorationSet {
    return DecorationSet.create(
        doc,
        issues.filter(isLocated).map((issue) => {
            const classes = [
                'proofread-issue',
                `proofread-${issue.category}`,
                `proofread-severity-${issue.severity}`,
                ...(highlightedIssueId === issue.id
                    ? ['proofread-issue-highlight']
                    : []),
            ].join(' ');

            return Decoration.inline(
                issue.from,
                issue.to,
                {
                    class: classes,
                    'data-proofread-id': issue.id,
                },
                {
                    id: issue.id,
                    issue,
                },
            );
        }),
    );
}

function remapIssues(
    issues: MappedProofreadIssue[],
    mapping: Mapping,
): MappedProofreadIssue[] {
    return issues.map((issue) => {
        if (!isLocated(issue)) {
            return issue;
        }

        const from = mapping.map(issue.from);
        const to = mapping.map(issue.to);

        if (from >= to) {
            return { ...issue, from: null, to: null };
        }

        return { ...issue, from, to };
    });
}

function emptyState(): ProofreadPluginState {
    return {
        issues: [],
        decorations: DecorationSet.empty,
        highlightedIssueId: null,
    };
}

function applyMeta(
    meta: ProofreadMeta,
    value: ProofreadPluginState,
    doc: ProseMirrorNode,
): ProofreadPluginState {
    if (meta.type === 'setIssues') {
        return {
            issues: meta.issues,
            highlightedIssueId: null,
            decorations: createDecorations(doc, meta.issues),
        };
    }

    if (meta.type === 'clear') {
        return emptyState();
    }

    if (meta.type === 'highlight') {
        return {
            ...value,
            highlightedIssueId: meta.id,
            decorations: createDecorations(doc, value.issues, meta.id),
        };
    }

    if (meta.type === 'clearHighlight') {
        return {
            ...value,
            highlightedIssueId: null,
            decorations: createDecorations(doc, value.issues),
        };
    }

    const issues = value.issues.filter((issue) => issue.id !== meta.id);
    const highlightedIssueId =
        value.highlightedIssueId === meta.id ? null : value.highlightedIssueId;

    return {
        issues,
        highlightedIssueId,
        decorations: createDecorations(doc, issues, highlightedIssueId),
    };
}

function getPluginState(state: EditorState): ProofreadPluginState {
    return proofreadPluginKey.getState(state) ?? emptyState();
}

export function getProofreadIssues(
    editor: Editor | null,
): MappedProofreadIssue[] {
    if (!editor) {
        return [];
    }

    return getPluginState(editor.state).issues;
}

export function getProofreadIssueById(
    editor: Editor | null,
    id: string,
): MappedProofreadIssue | null {
    return getProofreadIssues(editor).find((issue) => issue.id === id) ?? null;
}

export function focusProofreadIssue(
    editor: Editor,
    issue: MappedProofreadIssue,
): void {
    if (!isLocated(issue)) {
        highlightProofreadIssue(editor, issue.id);

        return;
    }

    editor
        .chain()
        .focus()
        .setTextSelection({ from: issue.from, to: issue.to })
        .run();

    highlightProofreadIssue(editor, issue.id);

    const dom = editor.view.dom.querySelector<HTMLElement>(
        `[data-proofread-id="${CSS.escape(issue.id)}"]`,
    );

    dom?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

let highlightTimeoutId: ReturnType<typeof setTimeout> | null = null;

export function highlightProofreadIssue(editor: Editor, issueId: string): void {
    editor.commands.highlightProofreadIssue(issueId);

    if (highlightTimeoutId !== null) {
        clearTimeout(highlightTimeoutId);
    }

    highlightTimeoutId = setTimeout(() => {
        editor.commands.clearProofreadHighlight();
        highlightTimeoutId = null;
    }, 3000);
}

export const Proofread = Extension.create({
    name: 'proofread',

    addCommands() {
        return {
            setProofreadIssues:
                (issues) =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(proofreadPluginKey, {
                            type: 'setIssues',
                            issues,
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            clearProofread:
                () =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(proofreadPluginKey, {
                            type: 'clear',
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            applyProofreadSuggestion:
                (id, value) =>
                ({ editor, tr, dispatch }) => {
                    const issue = getPluginState(editor.state).issues.find(
                        (entry) => entry.id === id,
                    );

                    if (
                        !issue ||
                        issue.from === null ||
                        issue.to === null ||
                        value === ''
                    ) {
                        return false;
                    }

                    if (dispatch) {
                        tr.insertText(value, issue.from, issue.to);
                        tr.setMeta(proofreadPluginKey, {
                            type: 'dismiss',
                            id,
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            dismissProofreadIssue:
                (id) =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(proofreadPluginKey, {
                            type: 'dismiss',
                            id,
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            highlightProofreadIssue:
                (id) =>
                ({ editor, tr, dispatch }) => {
                    const issue = getPluginState(editor.state).issues.find(
                        (entry) => entry.id === id,
                    );

                    if (!issue) {
                        return false;
                    }

                    if (dispatch) {
                        tr.setMeta(proofreadPluginKey, {
                            type: 'highlight',
                            id,
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
            clearProofreadHighlight:
                () =>
                ({ tr, dispatch }) => {
                    if (dispatch) {
                        tr.setMeta(proofreadPluginKey, {
                            type: 'clearHighlight',
                        } satisfies ProofreadMeta);
                        dispatch(tr);
                    }

                    return true;
                },
        };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin<ProofreadPluginState>({
                key: proofreadPluginKey,
                state: {
                    init: () => emptyState(),
                    apply: (
                        tr: Transaction,
                        value: ProofreadPluginState,
                    ): ProofreadPluginState => {
                        const meta = tr.getMeta(proofreadPluginKey) as
                            | ProofreadMeta
                            | undefined;

                        if (meta) {
                            if (meta.type === 'dismiss' && tr.docChanged) {
                                const remapped = remapIssues(
                                    value.issues,
                                    tr.mapping,
                                ).filter((issue) => issue.id !== meta.id);
                                const highlightedIssueId =
                                    value.highlightedIssueId === meta.id
                                        ? null
                                        : value.highlightedIssueId;

                                return {
                                    issues: remapped,
                                    highlightedIssueId,
                                    decorations: createDecorations(
                                        tr.doc,
                                        remapped,
                                        highlightedIssueId,
                                    ),
                                };
                            }

                            return applyMeta(meta, value, tr.doc);
                        }

                        if (!tr.docChanged) {
                            return value;
                        }

                        const issues = remapIssues(value.issues, tr.mapping);
                        const highlightedIssueId =
                            value.highlightedIssueId &&
                            issues.some(
                                (issue) =>
                                    issue.id === value.highlightedIssueId,
                            )
                                ? value.highlightedIssueId
                                : null;

                        return {
                            issues,
                            highlightedIssueId,
                            decorations: createDecorations(
                                tr.doc,
                                issues,
                                highlightedIssueId,
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
