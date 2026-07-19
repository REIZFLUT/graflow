import { NodeSelection } from '@tiptap/pm/state';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import {
    Bold,
    CaseSensitive,
    Heading2,
    Heading3,
    Image,
    ImageMinus,
    Italic,
    LayoutPanelTop,
    List,
    ListOrdered,
    MessageSquarePlus,
    Pilcrow,
    Quote,
    Sigma,
    SpellCheck2,
    SquareAsterisk,
    SquareFunction,
    Subscript,
    Superscript,
} from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import { focusMarginalNoteForSelection } from '@/components/articles/marginal-notes-column';
import { SpecialFormatToolbarMenu } from '@/components/articles/special-format-toolbar-menu';
import { TableToolbarMenu } from '@/components/articles/table-toolbar-menu';
import { SquareArrowRightExitIcon } from '@/components/icons/square-arrow-right-exit-icon';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import {
    createBlockElements,
    createCharacterFormats,
    createNormalCharacterFormat,
    createNormalParagraphFormat,
    createParagraphFormats,
    NORMAL_FORMAT_ID,
    createArticleEditorExtensions,
    getParagraphAtSelection,
    getTopLevelBlockAtSelection,
    selectArticleImageFigure,
} from '@/lib/tiptap';
import { cn } from '@/lib/utils';
import type { TipTapDocument } from '@/types';

type TipTapEditorProps = {
    content: TipTapDocument;
    onChange: (content: TipTapDocument) => void;
    readOnly?: boolean;
    className?: string;
    variant?: 'default' | 'document';
    onEditorReady?: (editor: Editor) => void;
    onFootnoteMarkClick?: (footnoteId: string) => void;
    onCommentMarkClick?: (threadId: string) => void;
    onSpellCheckMarkClick?: (matchId: string, rect: DOMRect) => void;
    onArticleImageSelect?: (mediaId: string) => void;
    onArticleImageDoubleClick?: (mediaId: string) => void;
    onInlineMathClick?: (latex: string, pos: number) => void;
    onBlockMathClick?: (latex: string, pos: number) => void;
};

type ToolbarButtonProps = {
    onClick: () => void;
    isActive?: boolean;
    disabled?: boolean;
    label: string;
    children: React.ReactNode;
};

function ToolbarButton({
    onClick,
    isActive = false,
    disabled = false,
    label,
    children,
}: ToolbarButtonProps) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onMouseDown={(event) => {
                event.preventDefault();
            }}
            onClick={onClick}
            className={cn(
                'inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground disabled:pointer-events-none disabled:opacity-40',
                isActive && 'bg-muted text-foreground',
            )}
        >
            {children}
        </button>
    );
}

export function TipTapToolbar({
    editor,
    showMarginalNotes = true,
    onFootnoteClick,
    onCommentClick,
    canComment = false,
    onImageClick,
    onRemoveArticleImage,
    onInlineMathClick,
    onBlockMathClick,
    onSpellCheckClick,
    isSpellChecking = false,
}: {
    editor: Editor;
    showMarginalNotes?: boolean;
    onFootnoteClick?: () => void;
    onCommentClick?: () => void;
    canComment?: boolean;
    onImageClick?: () => void;
    onRemoveArticleImage?: () => void;
    onInlineMathClick?: () => void;
    onBlockMathClick?: () => void;
    onSpellCheckClick?: () => void;
    isSpellChecking?: boolean;
}) {
    const { t } = useTranslation();
    const normalParagraphFormat = useMemo(
        () => createNormalParagraphFormat(t),
        [t],
    );
    const normalCharacterFormat = useMemo(
        () => createNormalCharacterFormat(t),
        [t],
    );
    const paragraphFormats = useMemo(() => createParagraphFormats(t), [t]);
    const characterFormats = useMemo(() => createCharacterFormats(t), [t]);
    const blockElements = useMemo(() => createBlockElements(t), [t]);

    const editorState = useEditorState({
        editor,
        selector: ({ editor: currentEditor }) => {
            const marginalBlock = getTopLevelBlockAtSelection(currentEditor);
            const paragraph = getParagraphAtSelection(currentEditor.state);
            const { empty } = currentEditor.state.selection;
            const paragraphFormat =
                (paragraph?.node.attrs.paragraphFormat as string | null) ??
                null;
            const activeCharacterFormat = characterFormats.find((format) =>
                currentEditor.isActive('characterFormat', {
                    className: format.className,
                }),
            );

            return {
                h2: currentEditor.isActive('heading', { level: 2 }),
                h3: currentEditor.isActive('heading', { level: 3 }),
                bold: currentEditor.isActive('bold'),
                italic: currentEditor.isActive('italic'),
                superscript: currentEditor.isActive('superscript'),
                subscript: currentEditor.isActive('subscript'),
                bulletList: currentEditor.isActive('bulletList'),
                orderedList: currentEditor.isActive('orderedList'),
                blockquote: currentEditor.isActive('blockquote'),
                hasMarginalBlock: marginalBlock !== null,
                hasMarginalNote: Boolean(
                    marginalBlock?.node.attrs.marginalNote,
                ),
                hasTextSelection: !empty,
                hasFootnote: currentEditor.isActive('footnote'),
                hasComment: currentEditor.isActive('comment'),
                hasSelectedArticleImage: currentEditor.isActive('articleImage'),
                activeParagraphFormat: paragraphFormat,
                hasParagraphFormat: paragraphFormat !== null,
                activeCharacterFormatId:
                    activeCharacterFormat?.id ?? NORMAL_FORMAT_ID,
                hasCharacterFormat: activeCharacterFormat !== undefined,
                isInInfoBox: currentEditor.isActive('infoBox'),
            };
        },
    });

    return (
        <div className="flex w-full flex-wrap items-center gap-0.5">
            <ToolbarButton
                label={t('editor.toolbar.heading_2')}
                isActive={editorState.h2}
                onClick={() =>
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                }
            >
                <Heading2 className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.heading_3')}
                isActive={editorState.h3}
                onClick={() =>
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                }
            >
                <Heading3 className="size-4 stroke-[1.75]" />
            </ToolbarButton>

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <ToolbarButton
                label={t('editor.toolbar.bold')}
                isActive={editorState.bold}
                onClick={() => editor.chain().focus().toggleBold().run()}
            >
                <Bold className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.italic')}
                isActive={editorState.italic}
                onClick={() => editor.chain().focus().toggleItalic().run()}
            >
                <Italic className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.superscript')}
                isActive={editorState.superscript}
                onClick={() =>
                    editor
                        .chain()
                        .focus()
                        .unsetSubscript()
                        .toggleSuperscript()
                        .run()
                }
            >
                <Superscript className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.subscript')}
                isActive={editorState.subscript}
                onClick={() =>
                    editor
                        .chain()
                        .focus()
                        .unsetSuperscript()
                        .toggleSubscript()
                        .run()
                }
            >
                <Subscript className="size-4 stroke-[1.75]" />
            </ToolbarButton>

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <ToolbarButton
                label={t('editor.toolbar.inline_math')}
                onClick={() => onInlineMathClick?.()}
            >
                <Sigma className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.block_math')}
                onClick={() => onBlockMathClick?.()}
            >
                <SquareFunction className="size-4 stroke-[1.75]" />
            </ToolbarButton>

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <ToolbarButton
                label={t('editor.toolbar.bullet_list')}
                isActive={editorState.bulletList}
                onClick={() => editor.chain().focus().toggleBulletList().run()}
            >
                <List className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.ordered_list')}
                isActive={editorState.orderedList}
                onClick={() => editor.chain().focus().toggleOrderedList().run()}
            >
                <ListOrdered className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.blockquote')}
                isActive={editorState.blockquote}
                onClick={() => editor.chain().focus().toggleBlockquote().run()}
            >
                <Quote className="size-4 stroke-[1.75]" />
            </ToolbarButton>

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <SpecialFormatToolbarMenu
                editor={editor}
                label={t('editor.toolbar.paragraph_formats')}
                icon={<Pilcrow className="size-4 stroke-[1.75]" />}
                formats={[normalParagraphFormat, ...paragraphFormats]}
                activeId={editorState.activeParagraphFormat ?? NORMAL_FORMAT_ID}
                isActive={editorState.hasParagraphFormat}
                onSelect={(formatId) => {
                    if (formatId === NORMAL_FORMAT_ID) {
                        editor.chain().focus().setParagraphFormat(null).run();

                        return;
                    }

                    const format = paragraphFormats.find(
                        (entry) => entry.id === formatId,
                    );

                    if (!format) {
                        return;
                    }

                    editor
                        .chain()
                        .focus()
                        .toggleParagraphFormat(format.id)
                        .run();
                }}
            />
            <SpecialFormatToolbarMenu
                editor={editor}
                label={t('editor.toolbar.character_formats')}
                icon={<CaseSensitive className="size-4 stroke-[1.75]" />}
                formats={[normalCharacterFormat, ...characterFormats]}
                activeId={editorState.activeCharacterFormatId}
                isActive={editorState.hasCharacterFormat}
                disabled={!editorState.hasTextSelection}
                onSelect={(formatId) => {
                    if (formatId === NORMAL_FORMAT_ID) {
                        editor.chain().focus().unsetCharacterFormat().run();

                        return;
                    }

                    const format = characterFormats.find(
                        (entry) => entry.id === formatId,
                    );

                    if (!format) {
                        return;
                    }

                    editor
                        .chain()
                        .focus()
                        .toggleCharacterFormat(format.className)
                        .run();
                }}
            />
            <SpecialFormatToolbarMenu
                editor={editor}
                label={t('editor.toolbar.block_elements')}
                icon={<LayoutPanelTop className="size-4 stroke-[1.75]" />}
                formats={blockElements}
                activeId={editorState.isInInfoBox ? 'infokasten' : null}
                isActive={editorState.isInInfoBox}
                onSelect={(formatId) => {
                    if (formatId === 'infokasten') {
                        editor.chain().focus().insertInfoBox().run();
                    }
                }}
            />

            <TableToolbarMenu editor={editor} />

            {showMarginalNotes && (
                <>
                    <div
                        className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                        aria-hidden
                    />

                    <ToolbarButton
                        label={t('editor.toolbar.marginal_note')}
                        isActive={editorState.hasMarginalNote}
                        disabled={!editorState.hasMarginalBlock}
                        onClick={() => focusMarginalNoteForSelection(editor)}
                    >
                        <SquareArrowRightExitIcon className="size-4 stroke-[1.75]" />
                    </ToolbarButton>
                </>
            )}

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <ToolbarButton
                label={t('editor.toolbar.footnote')}
                isActive={editorState.hasFootnote}
                disabled={!editorState.hasTextSelection}
                onClick={() => onFootnoteClick?.()}
            >
                <SquareAsterisk className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            {canComment && (
                <ToolbarButton
                    label={t('editor.toolbar.comment')}
                    isActive={editorState.hasComment}
                    disabled={!editorState.hasTextSelection}
                    onClick={() => onCommentClick?.()}
                >
                    <MessageSquarePlus className="size-4 stroke-[1.75]" />
                </ToolbarButton>
            )}
            <ToolbarButton
                label={t('editor.toolbar.image')}
                onClick={() => onImageClick?.()}
            >
                <Image className="size-4 stroke-[1.75]" />
            </ToolbarButton>
            <ToolbarButton
                label={t('editor.toolbar.remove_image')}
                isActive={editorState.hasSelectedArticleImage}
                disabled={!editorState.hasSelectedArticleImage}
                onClick={() => onRemoveArticleImage?.()}
            >
                <ImageMinus className="size-4 stroke-[1.75]" />
            </ToolbarButton>

            <div
                className="mx-1.5 h-5 w-px shrink-0 bg-border/60"
                aria-hidden
            />

            <ToolbarButton
                label={t('editor.toolbar.spellcheck')}
                disabled={isSpellChecking || !onSpellCheckClick}
                onClick={() => onSpellCheckClick?.()}
            >
                {isSpellChecking ? (
                    <Spinner className="size-4" />
                ) : (
                    <SpellCheck2 className="size-4 stroke-[1.75]" />
                )}
            </ToolbarButton>
        </div>
    );
}

export default function TipTapEditor({
    content,
    onChange,
    readOnly = false,
    className,
    variant = 'default',
    onEditorReady,
    onFootnoteMarkClick,
    onCommentMarkClick,
    onSpellCheckMarkClick,
    onArticleImageSelect,
    onArticleImageDoubleClick,
    onInlineMathClick,
    onBlockMathClick,
}: TipTapEditorProps) {
    const { t } = useTranslation();
    const isDocument = variant === 'document';
    const onFootnoteMarkClickRef = useRef(onFootnoteMarkClick);
    const onCommentMarkClickRef = useRef(onCommentMarkClick);
    const onSpellCheckMarkClickRef = useRef(onSpellCheckMarkClick);
    const onArticleImageSelectRef = useRef(onArticleImageSelect);
    const onArticleImageDoubleClickRef = useRef(onArticleImageDoubleClick);
    const onInlineMathClickRef = useRef(onInlineMathClick);
    const onBlockMathClickRef = useRef(onBlockMathClick);
    const lastEmittedContentRef = useRef<string | null>(
        JSON.stringify(content),
    );

    useEffect(() => {
        onFootnoteMarkClickRef.current = onFootnoteMarkClick;
        onCommentMarkClickRef.current = onCommentMarkClick;
        onSpellCheckMarkClickRef.current = onSpellCheckMarkClick;
        onArticleImageSelectRef.current = onArticleImageSelect;
        onArticleImageDoubleClickRef.current = onArticleImageDoubleClick;
        onInlineMathClickRef.current = onInlineMathClick;
        onBlockMathClickRef.current = onBlockMathClick;
    }, [
        onArticleImageDoubleClick,
        onArticleImageSelect,
        onBlockMathClick,
        onCommentMarkClick,
        onFootnoteMarkClick,
        onInlineMathClick,
        onSpellCheckMarkClick,
    ]);

    // Extensions close over refs so math click handlers stay stable without remounting the editor.
    const extensions = useMemo(() => {
        /* eslint-disable react-hooks/refs -- intentional stable callback refs for TipTap extensions */
        return createArticleEditorExtensions({
            placeholder: isDocument
                ? t('editor.placeholder.document')
                : t('editor.placeholder.default'),
            onInlineMathClick: (node, pos) => {
                onInlineMathClickRef.current?.(node.attrs.latex as string, pos);
            },
            onBlockMathClick: (node, pos) => {
                onBlockMathClickRef.current?.(node.attrs.latex as string, pos);
            },
        });
        /* eslint-enable react-hooks/refs */
    }, [isDocument, t]);

    const editor = useEditor({
        extensions,
        content,
        editable: !readOnly,
        editorProps: {
            attributes: {
                class: cn(
                    'max-w-none focus:outline-none',
                    isDocument
                        ? 'article-prose min-h-[480px] [&_.is-editor-empty:first-child::before]:text-muted-foreground/60'
                        : 'prose prose-sm dark:prose-invert min-h-[360px] px-5 py-4',
                ),
            },
            handleClick: (_view, _pos, event) => {
                const spellcheckElement = (event.target as HTMLElement).closest(
                    '.spellcheck-error',
                );

                if (spellcheckElement) {
                    const matchId =
                        spellcheckElement.getAttribute('data-spellcheck-id');

                    if (matchId) {
                        event.preventDefault();
                        onSpellCheckMarkClickRef.current?.(
                            matchId,
                            spellcheckElement.getBoundingClientRect(),
                        );

                        return true;
                    }
                }

                const commentElement = (event.target as HTMLElement).closest(
                    '.article-comment-mark',
                );

                if (commentElement) {
                    const threadId = commentElement.getAttribute(
                        'data-comment-thread-id',
                    );

                    if (threadId) {
                        event.preventDefault();
                        onCommentMarkClickRef.current?.(threadId);

                        return true;
                    }
                }

                const markElement = (event.target as HTMLElement).closest(
                    '.article-footnote-mark',
                );

                if (!markElement) {
                    return false;
                }

                const footnoteId = markElement.getAttribute('data-footnote-id');

                if (!footnoteId) {
                    return false;
                }

                event.preventDefault();
                onFootnoteMarkClickRef.current?.(footnoteId);

                return true;
            },
            handleDOMEvents: {
                click: (view, event) => {
                    const figure = (event.target as HTMLElement).closest(
                        'figure[data-article-image]',
                    );

                    if (!figure || !view.editable) {
                        return false;
                    }

                    event.preventDefault();

                    const mediaId = selectArticleImageFigure(view, figure);

                    if (mediaId) {
                        onArticleImageSelectRef.current?.(mediaId);
                    }

                    return true;
                },
                dblclick: (view, event) => {
                    const figure = (event.target as HTMLElement).closest(
                        'figure[data-article-image]',
                    );

                    if (!figure || !view.editable) {
                        return false;
                    }

                    event.preventDefault();

                    const mediaId = figure.getAttribute('data-media-id');

                    if (mediaId) {
                        onArticleImageDoubleClickRef.current?.(mediaId);
                    }

                    return true;
                },
            },
            handleKeyDown: (view, event) => {
                if (event.key !== 'Delete' && event.key !== 'Backspace') {
                    return false;
                }

                const { selection } = view.state;

                if (
                    !(selection instanceof NodeSelection) ||
                    selection.node.type.name !== 'articleImage'
                ) {
                    return false;
                }

                event.preventDefault();
                view.dispatch(view.state.tr.deleteSelection());

                return true;
            },
        },
        onUpdate: ({ editor: currentEditor }) => {
            const json = currentEditor.getJSON() as TipTapDocument;
            lastEmittedContentRef.current = JSON.stringify(json);
            onChange(json);
        },
    });

    useEffect(() => {
        if (editor && onEditorReady) {
            onEditorReady(editor);
        }
    }, [editor, onEditorReady]);

    useEffect(() => {
        editor?.setEditable(!readOnly);
    }, [editor, readOnly]);

    useEffect(() => {
        if (!editor) {
            return;
        }

        const nextContent = JSON.stringify(content);

        if (nextContent === lastEmittedContentRef.current) {
            return;
        }

        lastEmittedContentRef.current = nextContent;
        editor.commands.setContent(content, { emitUpdate: false });
    }, [content, editor]);

    if (!editor) {
        return null;
    }

    if (isDocument) {
        return (
            <div className={cn('article-document-body', className)}>
                <EditorContent editor={editor} />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border border-input bg-background',
                className,
            )}
        >
            <div className="border-b border-input bg-muted/30 px-3 py-2.5">
                <TipTapToolbar editor={editor} />
            </div>

            <EditorContent editor={editor} />
        </div>
    );
}
