import { Node, mergeAttributes } from '@tiptap/core';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        infoBox: {
            insertInfoBox: () => ReturnType;
        };
    }
}

export const InfoBox = Node.create({
    name: 'infoBox',

    group: 'block',

    content: 'block+',

    defining: true,

    parseHTML() {
        return [
            {
                tag: 'div.infokasten[data-type="info-box"]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                class: 'infokasten',
                'data-type': 'info-box',
            }),
            0,
        ];
    },

    addCommands() {
        return {
            insertInfoBox:
                () =>
                ({ chain, state }) => {
                    const { $from } = state.selection;

                    for (let depth = $from.depth; depth > 0; depth--) {
                        const node = $from.node(depth);

                        if (node.type.name !== 'paragraph') {
                            continue;
                        }

                        const pos = $from.before(depth);

                        return chain()
                            .command(({ tr }) => {
                                const infoBox = state.schema.nodes.infoBox;
                                const paragraph = state.schema.nodes.paragraph;

                                if (!infoBox || !paragraph) {
                                    return false;
                                }

                                const wrapped = infoBox.create(
                                    {},
                                    node.content.size > 0
                                        ? [node]
                                        : [paragraph.create()],
                                );

                                tr.replaceWith(pos, pos + node.nodeSize, wrapped);

                                return true;
                            })
                            .run();
                    }

                    return chain()
                        .insertContent({
                            type: this.name,
                            content: [{ type: 'paragraph' }],
                        })
                        .run();
                },
        };
    },
});
