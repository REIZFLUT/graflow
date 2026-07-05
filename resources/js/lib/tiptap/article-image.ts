import { Node, mergeAttributes } from '@tiptap/core';

export type ArticleImageAttributes = {
    mediaId: string;
    alt: string;
    copyright: string;
    caption: string | null;
    previewWebpUrl: string;
    previewJpegUrl: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        articleImage: {
            setArticleImage: (attrs: ArticleImageAttributes) => ReturnType;
            updateArticleImageById: (
                mediaId: string,
                attrs: Partial<ArticleImageAttributes>,
            ) => ReturnType;
        };
    }
}

export const ArticleImage = Node.create({
    name: 'articleImage',

    group: 'block',

    atom: true,

    selectable: true,

    draggable: true,

    addAttributes() {
        return {
            mediaId: { default: null },
            alt: { default: '' },
            copyright: { default: '' },
            caption: { default: null },
            previewWebpUrl: { default: '' },
            previewJpegUrl: { default: '' },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-article-image]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const caption = HTMLAttributes.caption as string | null;
        const copyright = HTMLAttributes.copyright as string;

        const children: Array<
            | ['picture', Record<string, unknown>, ...unknown[]]
            | ['figcaption', Record<string, unknown>, string]
            | ['span', Record<string, unknown>, string]
        > = [
            [
                'picture',
                {},
                [
                    'source',
                    {
                        srcset: HTMLAttributes.previewWebpUrl,
                        type: 'image/webp',
                    },
                ],
                [
                    'img',
                    mergeAttributes(
                        {
                            src: HTMLAttributes.previewJpegUrl,
                            alt: HTMLAttributes.alt,
                            loading: 'lazy',
                            class: 'article-image-img',
                        },
                        {},
                    ),
                ],
            ],
        ];

        if (caption) {
            children.push(['figcaption', { class: 'article-image-caption' }, caption]);
        }

        if (copyright) {
            children.push([
                'span',
                { class: 'article-image-copyright' },
                `© ${copyright}`,
            ]);
        }

        return [
            'figure',
            mergeAttributes(HTMLAttributes, {
                'data-article-image': '',
                'data-media-id': HTMLAttributes.mediaId,
                class: 'article-image',
                contenteditable: 'false',
            }),
            ...children,
        ];
    },

    addCommands() {
        return {
            setArticleImage:
                (attrs) =>
                ({ commands }) =>
                    commands.insertContent({
                        type: this.name,
                        attrs,
                    }),

            updateArticleImageById:
                (mediaId, attrs) =>
                ({ tr, state, dispatch }) => {
                    let updated = false;

                    state.doc.descendants((node, pos) => {
                        if (
                            node.type.name !== this.name ||
                            node.attrs.mediaId !== mediaId
                        ) {
                            return;
                        }

                        tr.setNodeMarkup(pos, undefined, {
                            ...node.attrs,
                            ...attrs,
                        });
                        updated = true;
                    });

                    if (updated && dispatch) {
                        dispatch(tr);
                    }

                    return updated;
                },
        };
    },
});
