import type { JSONContent } from '@tiptap/core';
import { Image, Text, View } from '@react-pdf/renderer';
import type { ReactNode } from 'react';
import {
    footnoteIndexForId,
    type CollectedFootnote,
} from '@/lib/article-pdf/footnotes';
import { toSuperscriptNumber } from '@/lib/article-pdf/superscript';
import { toPdfImageSource } from '@/lib/article-pdf/image-source';
import { mathImageDimensions } from '@/lib/article-pdf/math-image-style';
import type { ArticlePdfStyles } from '@/lib/article-pdf/styles';
import type { ArticleMedia } from '@/types';

type RenderContext = {
    styles: ArticlePdfStyles;
    mediaById: Map<string, ArticleMedia>;
    footnotes: CollectedFootnote[];
    hasMarginalColumn: boolean;
    listMarker?: string;
    compactBlock?: boolean;
};

export function renderTipTapNode(
    node: JSONContent,
    context: RenderContext,
    key: string,
): ReactNode {
    switch (node.type) {
        case 'doc':
            return (
                <View key={key}>
                    {renderDocChildren(node, context, key)}
                </View>
            );
        case 'paragraph':
            return renderTextBlock(
                node,
                context,
                key,
                context.compactBlock
                    ? context.styles.paragraphCompact
                    : context.styles.paragraph,
            );
        case 'heading': {
            const level = Number(node.attrs?.level ?? 2);
            const style =
                level === 3 ? context.styles.h3 : context.styles.h2;

            return renderTextBlock(node, context, key, style);
        }
        case 'blockquote':
            return wrapMarginalBlock(
                node,
                context,
                key,
                <View style={context.styles.blockquote}>
                    {renderChildren(
                        node,
                        { ...context, compactBlock: true },
                        key,
                    )}
                </View>,
            );
        case 'bulletList':
            return wrapMarginalBlock(
                node,
                context,
                key,
                <View style={context.styles.list}>
                    {node.content?.map((child, index) =>
                        renderTipTapNode(
                            child,
                            { ...context, listMarker: '•' },
                            `${key}-${index}`,
                        ),
                    )}
                </View>,
            );
        case 'orderedList':
            return wrapMarginalBlock(
                node,
                context,
                key,
                <View style={context.styles.list}>
                    {node.content?.map((child, index) =>
                        renderTipTapNode(
                            child,
                            {
                                ...context,
                                listMarker: `${index + 1}.`,
                            },
                            `${key}-${index}`,
                        ),
                    )}
                </View>,
            );
        case 'listItem':
            return (
                <View key={key} style={context.styles.listItem}>
                    {context.listMarker ? (
                        <Text style={context.styles.listMarker}>
                            {context.listMarker}
                        </Text>
                    ) : null}
                    <View style={context.styles.listItemContent}>
                        {renderChildren(
                            node,
                            {
                                ...context,
                                listMarker: undefined,
                                compactBlock: true,
                            },
                            key,
                        )}
                    </View>
                </View>
            );
        case 'infoBox':
            return wrapMarginalBlock(
                node,
                context,
                key,
                <View style={context.styles.infoBox}>
                    {renderChildren(
                        node,
                        { ...context, compactBlock: true },
                        key,
                    )}
                </View>,
            );
        case 'articleImage':
            return wrapMarginalBlock(
                node,
                context,
                key,
                renderArticleImageContent(node, context, key),
            );
        case 'pdfMathImage':
            return wrapMarginalBlock(
                node,
                context,
                key,
                renderMathImageContent(node, context, key),
            );
        case 'table':
            return wrapMarginalBlock(
                node,
                context,
                key,
                <View style={context.styles.table}>
                    {renderChildren(
                        node,
                        { ...context, compactBlock: true },
                        key,
                    )}
                </View>,
                { keepTogether: true },
            );
        case 'tableRow':
            return (
                <View key={key} style={context.styles.tableRow}>
                    {renderChildren(node, context, key)}
                </View>
            );
        case 'tableHeader':
            return (
                <View key={key} style={context.styles.tableHeaderCell}>
                    {renderChildren(
                        node,
                        { ...context, compactBlock: true },
                        key,
                    )}
                </View>
            );
        case 'tableCell':
            return (
                <View key={key} style={context.styles.tableCell}>
                    {renderChildren(
                        node,
                        { ...context, compactBlock: true },
                        key,
                    )}
                </View>
            );
        case 'hardBreak':
            return '\n';
        default:
            return renderChildren(node, context, key);
    }
}

function renderTextBlock(
    node: JSONContent,
    context: RenderContext,
    key: string,
    style: ArticlePdfStyles[keyof ArticlePdfStyles],
): ReactNode {
    const hasInlineMath = hasInlineMathChildren(node);

    return wrapMarginalBlock(
        node,
        context,
        key,
        <Text style={style}>
            {hasInlineMath
                ? renderInlineMixedContent(node, context)
                : renderInlineTextContent(node, context)}
        </Text>,
    );
}

function hasInlineMathChildren(node: JSONContent): boolean {
    if (!Array.isArray(node.content)) {
        return false;
    }

    return node.content.some((child) => child.type === 'pdfMathImage');
}

function wrapMarginalBlock(
    node: JSONContent,
    context: RenderContext,
    key: string,
    main: ReactNode,
    options?: { keepTogether?: boolean },
): ReactNode {
    const wrapProps = options?.keepTogether ? { wrap: false as const } : {};

    if (!context.hasMarginalColumn) {
        return (
            <View key={key} {...wrapProps}>
                {main}
            </View>
        );
    }

    const note = getMarginalNote(node);

    return (
        <View key={key} style={context.styles.marginalRow} {...wrapProps}>
            <View style={context.styles.marginalMain}>{main}</View>
            <View style={context.styles.marginalColumn}>
                {note ? (
                    <Text style={context.styles.marginalNote}>{note}</Text>
                ) : null}
            </View>
        </View>
    );
}

function getMarginalNote(node: JSONContent): string | null {
    const note = node.attrs?.marginalNote;

    if (!note || typeof note !== 'string') {
        return null;
    }

    const trimmed = note.trim();

    return trimmed === '' ? null : trimmed;
}

function renderDocChildren(
    node: JSONContent,
    context: RenderContext,
    keyPrefix: string,
): ReactNode {
    if (!Array.isArray(node.content)) {
        return null;
    }

    return node.content.map((child, index) =>
        renderTipTapNode(child, context, `${keyPrefix}-${index}`),
    );
}

function renderArticleImageContent(
    node: JSONContent,
    context: RenderContext,
    key: string,
): ReactNode {
    const mediaId = String(node.attrs?.mediaId ?? '');
    const media = context.mediaById.get(mediaId);
    const src = media?.original_url ?? media?.preview_jpeg_url;

    if (!src) {
        return null;
    }

    const caption =
        (node.attrs?.caption as string | null | undefined) ??
        media?.caption ??
        null;
    const copyright =
        (node.attrs?.copyright as string | undefined) ??
        media?.copyright ??
        '';

    return (
        <View style={context.styles.image}>
            <Image src={src} style={context.styles.imageAsset} />
            {caption ? (
                <Text style={context.styles.imageCaption}>{caption}</Text>
            ) : null}
            {copyright ? (
                <Text style={context.styles.imageCopyright}>
                    {`© ${copyright}`}
                </Text>
            ) : null}
        </View>
    );
}

function renderMathImageContent(
    node: JSONContent,
    context: RenderContext,
    key: string,
): ReactNode {
    const src = String(node.attrs?.src ?? '');

    if (!src) {
        return null;
    }

    const displayMode = Boolean(node.attrs?.displayMode);
    const dimensions = mathImageDimensions(
        Number(node.attrs?.width ?? 0),
        Number(node.attrs?.height ?? 0),
        displayMode,
    );
    const imageSource = toPdfImageSource(src);

    if (displayMode) {
        return (
            <View style={context.styles.mathBlock}>
                <Image src={imageSource} style={dimensions} />
            </View>
        );
    }

    return (
        <Image
            src={imageSource}
            style={{ ...context.styles.mathInline, ...dimensions }}
        />
    );
}

function renderChildren(
    node: JSONContent,
    context: RenderContext,
    keyPrefix: string,
): ReactNode {
    if (!Array.isArray(node.content)) {
        return null;
    }

    return node.content.map((child, index) =>
        renderTipTapNode(child, context, `${keyPrefix}-${index}`),
    );
}

function renderInlineMixedContent(
    node: JSONContent,
    context: RenderContext,
): ReactNode {
    if (!Array.isArray(node.content)) {
        return null;
    }

    return node.content.map((child, index) => {
        if (child.type === 'text') {
            return renderMarkedTextContent(
                child,
                context,
                `inline-text-${index}`,
            );
        }

        if (child.type === 'hardBreak') {
            return '\n';
        }

        if (child.type === 'pdfMathImage') {
            return renderInlineMathImage(
                child,
                context,
                `inline-math-${index}`,
            );
        }

        return null;
    });
}

function renderInlineMathImage(
    node: JSONContent,
    context: RenderContext,
    key: string,
): ReactNode {
    const src = String(node.attrs?.src ?? '');

    if (!src) {
        return null;
    }

    const dimensions = mathImageDimensions(
        Number(node.attrs?.width ?? 0),
        Number(node.attrs?.height ?? 0),
        false,
    );

    return (
        <Image
            key={key}
            src={toPdfImageSource(src)}
            style={{ ...context.styles.mathInline, ...dimensions }}
        />
    );
}

function renderInlineTextContent(
    node: JSONContent,
    context: RenderContext,
): ReactNode {
    if (!Array.isArray(node.content)) {
        return null;
    }

    return node.content.map((child, index) => {
        if (child.type === 'text') {
            return renderMarkedTextContent(
                child,
                context,
                `inline-text-${index}`,
            );
        }

        if (child.type === 'hardBreak') {
            return '\n';
        }

        return null;
    });
}

function renderMarkedTextContent(
    node: JSONContent,
    context: RenderContext,
    key: string,
): ReactNode {
    const text = node.text ?? '';
    let content: ReactNode = text;

    for (const mark of node.marks ?? []) {
        if (mark.type === 'bold') {
            content = (
                <Text key={`${key}-bold`} style={context.styles.bold}>
                    {content}
                </Text>
            );
        }

        if (mark.type === 'italic') {
            content = (
                <Text key={`${key}-italic`} style={context.styles.italic}>
                    {content}
                </Text>
            );
        }

        if (mark.type === 'superscript') {
            content = (
                <Text key={`${key}-sup`} style={context.styles.superscript}>
                    {content}
                </Text>
            );
        }

        if (mark.type === 'subscript') {
            content = (
                <Text key={`${key}-sub`} style={context.styles.subscript}>
                    {content}
                </Text>
            );
        }

        if (mark.type === 'footnote') {
            const index = footnoteIndexForId(
                context.footnotes,
                mark.attrs?.id as string | undefined,
            );

            if (index !== null) {
                content = (
                    <Text key={`${key}-fn-wrap`}>
                        {content}
                        <Text style={context.styles.footnoteMarker}>
                            {toSuperscriptNumber(index)}
                        </Text>
                    </Text>
                );
            }
        }
    }

    return content;
}
