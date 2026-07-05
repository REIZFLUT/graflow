import { Extension } from '@tiptap/core';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';

export function getParagraphAtSelection(
    state: import('@tiptap/pm/state').EditorState,
): { node: ProseMirrorNode; pos: number } | null {
    const { $from } = state.selection;

    for (let depth = $from.depth; depth > 0; depth--) {
        const node = $from.node(depth);

        if (node.type.name !== 'paragraph') {
            continue;
        }

        return {
            node,
            pos: $from.before(depth),
        };
    }

    return null;
}

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        paragraphFormat: {
            setParagraphFormat: (format: string | null) => ReturnType;
            toggleParagraphFormat: (format: string) => ReturnType;
        };
    }
}

export const ParagraphFormat = Extension.create({
    name: 'paragraphFormat',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph'],
                attributes: {
                    paragraphFormat: {
                        default: null,
                        parseHTML: (element) =>
                            element.getAttribute('data-paragraph-format'),
                        renderHTML: (attributes) => {
                            const format = attributes.paragraphFormat as
                                | string
                                | null;

                            if (!format) {
                                return {};
                            }

                            return {
                                class: format,
                                'data-paragraph-format': format,
                            };
                        },
                    },
                },
            },
        ];
    },

    addCommands() {
        return {
            setParagraphFormat:
                (format) =>
                ({ state, chain }) => {
                    const paragraph = getParagraphAtSelection(state);

                    if (!paragraph) {
                        return false;
                    }

                    return chain()
                        .command(({ tr }) => {
                            tr.setNodeMarkup(paragraph.pos, undefined, {
                                ...paragraph.node.attrs,
                                paragraphFormat: format,
                            });

                            return true;
                        })
                        .run();
                },

            toggleParagraphFormat:
                (format) =>
                ({ editor, commands }) => {
                    const paragraph = getParagraphAtSelection(editor.state);

                    if (!paragraph) {
                        return false;
                    }

                    const currentFormat = paragraph.node.attrs
                        .paragraphFormat as string | null;

                    if (currentFormat === format) {
                        return commands.setParagraphFormat(null);
                    }

                    return commands.setParagraphFormat(format);
                },
        };
    },
});
