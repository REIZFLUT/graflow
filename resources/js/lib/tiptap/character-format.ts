import { Mark, mergeAttributes } from '@tiptap/core';

export type CharacterFormatAttributes = {
    className: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        characterFormat: {
            setCharacterFormat: (className: string) => ReturnType;
            unsetCharacterFormat: () => ReturnType;
            toggleCharacterFormat: (className: string) => ReturnType;
        };
    }
}

export const CharacterFormat = Mark.create({
    name: 'characterFormat',

    excludes: '_',

    addAttributes() {
        return {
            className: {
                default: null,
                parseHTML: (element) => element.getAttribute('class'),
                renderHTML: (attributes) => {
                    if (!attributes.className) {
                        return {};
                    }

                    return {
                        class: attributes.className,
                    };
                },
            },
        };
    },

    parseHTML() {
        return CHARACTER_FORMAT_TAGS.map((className) => ({
            tag: `span.${className}`,
            getAttrs: (element) => {
                if (typeof element === 'string') {
                    return false;
                }

                return {
                    className: element.getAttribute('class'),
                };
            },
        }));
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                'data-character-format': HTMLAttributes.className,
            }),
            0,
        ];
    },

    addCommands() {
        return {
            setCharacterFormat:
                (className) =>
                ({ commands }) =>
                    commands.setMark(this.name, { className }),

            unsetCharacterFormat:
                () =>
                ({ commands }) =>
                    commands.unsetMark(this.name),

            toggleCharacterFormat:
                (className) =>
                ({ editor, commands }) => {
                    if (editor.isActive(this.name, { className })) {
                        return commands.unsetCharacterFormat();
                    }

                    return commands.setCharacterFormat(className);
                },
        };
    },
});

const CHARACTER_FORMAT_TAGS = ['text-red'] as const;
