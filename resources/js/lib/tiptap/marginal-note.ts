import { Extension } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import type { EditorState, Transaction } from '@tiptap/pm/state';

export const MARGINAL_NOTE_BLOCK_TYPES = [
    'paragraph',
    'heading',
    'blockquote',
    'bulletList',
    'orderedList',
] as const;

export type MarginalNoteBlockType = (typeof MARGINAL_NOTE_BLOCK_TYPES)[number];

export function isMarginalNoteBlockTypeName(
    type: string,
): type is MarginalNoteBlockType {
    return (MARGINAL_NOTE_BLOCK_TYPES as readonly string[]).includes(type);
}

function blockWithTextAndMarginalNoteExists(
    doc: ProseMirrorNode,
    typeName: string,
    text: string,
    marginalNote: string,
): boolean {
    let found = false;

    doc.descendants((node) => {
        if (found || node.type.name !== typeName) {
            return;
        }

        if (
            node.textContent === text &&
            node.attrs.marginalNote === marginalNote
        ) {
            found = true;
        }
    });

    return found;
}

export function clearMarginalNotesOnSplitBlocks(
    oldDoc: ProseMirrorNode,
    newState: EditorState,
    tr: Transaction,
): boolean {
    const blocksToClear: Array<{ pos: number; node: ProseMirrorNode }> = [];

    newState.doc.descendants((node, pos, parent) => {
        if (!parent || parent.type.name !== 'doc') {
            return;
        }

        if (!isMarginalNoteBlockTypeName(node.type.name)) {
            return;
        }

        const marginalNote = node.attrs.marginalNote as string | null;

        if (!marginalNote) {
            return;
        }

        const index = newState.doc.resolve(pos).index();

        if (index === 0) {
            return;
        }

        const previousSibling = parent.child(index - 1);

        if (previousSibling.attrs.marginalNote !== marginalNote) {
            return;
        }

        const combinedText = previousSibling.textContent + node.textContent;

        if (
            !blockWithTextAndMarginalNoteExists(
                oldDoc,
                node.type.name,
                combinedText,
                marginalNote,
            )
        ) {
            return;
        }

        blocksToClear.push({ pos, node });
    });

    if (blocksToClear.length === 0) {
        return false;
    }

    for (const { pos, node } of blocksToClear.sort(
        (left, right) => right.pos - left.pos,
    )) {
        tr.setNodeMarkup(pos, undefined, {
            ...node.attrs,
            marginalNote: null,
        });
    }

    return true;
}

export const MarginalNote = Extension.create({
    name: 'marginalNote',

    addGlobalAttributes() {
        return [
            {
                types: [...MARGINAL_NOTE_BLOCK_TYPES],
                attributes: {
                    marginalNote: {
                        default: null,
                        parseHTML: (element) =>
                            element.getAttribute('data-marginal-note'),
                        renderHTML: (attributes) => {
                            if (!attributes.marginalNote) {
                                return {};
                            }

                            return {
                                'data-marginal-note': attributes.marginalNote,
                            };
                        },
                    },
                },
            },
        ];
    },

    onTransaction({ editor, transaction }) {
        if (!transaction.docChanged || transaction.getMeta('marginalNoteSplit')) {
            return;
        }

        const oldDoc = transaction.docs[0];

        if (!oldDoc) {
            return;
        }

        const tr = editor.state.tr;

        if (!clearMarginalNotesOnSplitBlocks(oldDoc, editor.state, tr)) {
            return;
        }

        tr.setMeta('marginalNoteSplit', true);
        tr.setMeta('addToHistory', false);

        editor.view.dispatch(tr);
    },
});
