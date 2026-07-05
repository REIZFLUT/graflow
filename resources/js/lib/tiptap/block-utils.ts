import type { Editor } from '@tiptap/react';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import {
    MARGINAL_NOTE_BLOCK_TYPES,
    type MarginalNoteBlockType,
} from '@/lib/tiptap/marginal-note';

export type TopLevelBlock = {
    node: ProseMirrorNode;
    pos: number;
    type: MarginalNoteBlockType;
};

export type MarginalBlockInfo = TopLevelBlock & {
    id: string;
    marginalNote: string | null;
};

export function isMarginalNoteBlockType(
    type: string,
): type is MarginalNoteBlockType {
    return (MARGINAL_NOTE_BLOCK_TYPES as readonly string[]).includes(type);
}

export function getTopLevelBlockAtSelection(
    editor: Editor,
): TopLevelBlock | null {
    const { $from } = editor.state.selection;

    for (let depth = $from.depth; depth > 0; depth--) {
        if ($from.node(depth - 1).type.name !== 'doc') {
            continue;
        }

        const node = $from.node(depth);
        const type = node.type.name;

        if (!isMarginalNoteBlockType(type)) {
            return null;
        }

        return {
            node,
            pos: $from.before(depth),
            type,
        };
    }

    return null;
}

export function getTopLevelBlocksWithMarginalNotes(
    editor: Editor,
): MarginalBlockInfo[] {
    const blocks: MarginalBlockInfo[] = [];

    editor.state.doc.forEach((node, pos) => {
        if (node.type.name === 'footnotes') {
            return;
        }

        if (!isMarginalNoteBlockType(node.type.name)) {
            return;
        }

        const id = node.attrs.id as string | undefined;

        if (!id) {
            return;
        }

        blocks.push({
            node,
            pos,
            type: node.type.name,
            id,
            marginalNote: (node.attrs.marginalNote as string | null) ?? null,
        });
    });

    return blocks;
}

export function setMarginalNoteAtPosition(
    editor: Editor,
    pos: number,
    node: ProseMirrorNode,
    value: string,
): void {
    editor
        .chain()
        .command(({ tr }) => {
            tr.setNodeMarkup(pos, undefined, {
                ...node.attrs,
                marginalNote: value.trim() === '' ? null : value,
            });

            return true;
        })
        .run();
}
