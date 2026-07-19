import { Link, router } from '@inertiajs/react';
import type { Editor } from '@tiptap/react';
import {
    ArrowLeft,
    FileText,
    Image,
    Save,
    SquareAsterisk,
    Tags,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import ArticleEditorFooter from '@/components/articles/article-editor-footer';
import ArticleImageDialog from '@/components/articles/article-image-dialog';
import ArticleMediaPanel from '@/components/articles/article-media-panel';
import FootnoteDialog from '@/components/articles/footnote-dialog';
import FootnotesPanel from '@/components/articles/footnotes-panel';
import MarginalNotesColumn from '@/components/articles/marginal-notes-column';
import MathDialog from '@/components/articles/math-dialog';
import type {
    MathDialogMode,
    MathDialogVariant,
} from '@/components/articles/math-dialog';
import SpellCheckPanel from '@/components/articles/spellcheck-panel';
import SpellCheckPopover from '@/components/articles/spellcheck-popover';
import TipTapEditor, {
    TipTapToolbar,
} from '@/components/articles/tiptap-editor';
import VersionCompare from '@/components/articles/version-compare';
import VersionHistory from '@/components/articles/version-history';
import WorkflowHistoryPanel from '@/components/articles/workflow-history-panel';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useArticleEditorChrome } from '@/contexts/article-editor-chrome-context';
import type { ArticleMediaFormData } from '@/hooks/use-article-media';
import { useSpellCheck } from '@/hooks/use-spell-check';
import { useTranslation } from '@/hooks/use-translation';
import { generateArticlePdfBlob } from '@/lib/article-pdf/generate';
import { getArticleStatusLabel } from '@/lib/article-status';
import { combineDocumentText, getDocumentStats } from '@/lib/document-stats';
import {
    deleteSelectedArticleImage,
    focusFootnoteInEditor,
    getArticleImageMediaIdsFromEditor,
    getFootnoteAtSelection,
    getFootnoteById,
    getFootnotesFromEditor,
    getSelectedArticleImage,
    insertArticleImage,
    removeFootnoteById,
    syncArticleImagesFromMedia,
    trimSelectionBounds,
} from '@/lib/tiptap';
import type { ArticleFootnote, MappedSpellCheckMatch } from '@/lib/tiptap';
import { cn } from '@/lib/utils';
import { index } from '@/routes/articles';
import { edit as metadataEdit } from '@/routes/articles/metadata';
import { store as storeArticlePdf } from '@/routes/articles/pdfs';
import type {
    ArticleMedia,
    ArticleStatus,
    ArticleUser,
    ArticleVersion,
    ArticleWorkflowEvent,
    PublicationEditorSettings,
    TipTapDocument,
} from '@/types';

type ArticleDocumentEditorProps = {
    title: string;
    content: TipTapDocument;
    editorSettings: PublicationEditorSettings;
    onTitleChange: (title: string) => void;
    onContentChange: (content: TipTapDocument) => void;
    onSubmit: (content: TipTapDocument) => void;
    processing: boolean;
    errors: {
        title?: string;
        content?: string;
    };
    status?: ArticleStatus;
    readOnly?: boolean;
    canManageMetadata?: boolean;
    workflowActions?: ReactNode;
    articleId?: number;
    currentAssignee?: ArticleUser | null;
    submissionDeadline?: string | null;
    targetCharacterCount?: number | null;
    versions?: ArticleVersion[];
    workflowEvents?: ArticleWorkflowEvent[];
    mediaItems?: ArticleMedia[];
    mediaUploading?: boolean;
    onMediaUpload?: (
        file: File,
        metadata: ArticleMediaFormData,
    ) => Promise<ArticleMedia>;
    onMediaUpdate?: (
        mediaId: string,
        metadata: Partial<ArticleMediaFormData>,
    ) => Promise<ArticleMedia>;
    onMediaDelete?: (media: ArticleMedia) => Promise<void>;
};

export default function ArticleDocumentEditor({
    title,
    content,
    editorSettings,
    onTitleChange,
    onContentChange,
    onSubmit,
    processing,
    errors,
    status,
    readOnly = false,
    canManageMetadata = false,
    workflowActions = null,
    articleId,
    currentAssignee = null,
    submissionDeadline = null,
    targetCharacterCount = null,
    versions = [],
    workflowEvents = [],
    mediaItems = [],
    mediaUploading = false,
    onMediaUpload,
    onMediaUpdate,
    onMediaDelete,
}: ArticleDocumentEditorProps) {
    const { t, locale } = useTranslation();
    const { setChrome, clearChrome } = useArticleEditorChrome();
    const { isChecking, hasRun, runCheck } = useSpellCheck();
    const [editor, setEditor] = useState<Editor | null>(null);
    const [footnoteDialogOpen, setFootnoteDialogOpen] = useState(false);
    const [footnoteText, setFootnoteText] = useState('');
    const [footnoteExcerpt, setFootnoteExcerpt] = useState('');
    const [footnoteMode, setFootnoteMode] = useState<'create' | 'edit'>(
        'create',
    );
    const [footnoteCount, setFootnoteCount] = useState(0);
    const [footnotesSheetOpen, setFootnotesSheetOpen] = useState(false);
    const [focusedFootnoteId, setFocusedFootnoteId] = useState<string | null>(
        null,
    );
    const [spellCheckSheetOpen, setSpellCheckSheetOpen] = useState(false);
    const [focusedSpellCheckMatchId, setFocusedSpellCheckMatchId] = useState<
        string | null
    >(null);
    const [spellCheckPopoverMatchId, setSpellCheckPopoverMatchId] = useState<
        string | null
    >(null);
    const [spellCheckPopoverRect, setSpellCheckPopoverRect] =
        useState<DOMRect | null>(null);
    const [editingFootnoteId, setEditingFootnoteId] = useState<string | null>(
        null,
    );
    const [pendingFootnoteSelection, setPendingFootnoteSelection] = useState<{
        from: number;
        to: number;
    } | null>(null);
    const [imageDialogOpen, setImageDialogOpen] = useState(false);
    const [imageDialogMode, setImageDialogMode] = useState<'upload' | 'edit'>(
        'upload',
    );
    const [editingMedia, setEditingMedia] = useState<ArticleMedia | null>(null);
    const [mediaSheetOpen, setMediaSheetOpen] = useState(false);
    const [mathDialogOpen, setMathDialogOpen] = useState(false);
    const [mathLatex, setMathLatex] = useState('');
    const [mathVariant, setMathVariant] = useState<MathDialogVariant>('inline');
    const [mathMode, setMathMode] = useState<MathDialogMode>('create');
    const [editingMathPos, setEditingMathPos] = useState<number | null>(null);
    const [pdfExporting, setPdfExporting] = useState(false);
    const [versionView, setVersionView] = useState<'history' | 'compare'>(
        'history',
    );
    const [versionSheetOpen, setVersionSheetOpen] = useState(false);
    const [workflowHistorySheetOpen, setWorkflowHistorySheetOpen] =
        useState(false);
    const [compareBaseId, setCompareBaseId] = useState<number | null>(
        () => versions[1]?.id ?? null,
    );
    const [compareTargetId, setCompareTargetId] = useState<number | null>(
        () => versions[0]?.id ?? null,
    );

    const openMathDialogForCreate = useCallback(
        (variant: MathDialogVariant) => {
            setMathVariant(variant);
            setMathMode('create');
            setMathLatex('');
            setEditingMathPos(null);
            setMathDialogOpen(true);
        },
        [],
    );

    const openMathDialogForEdit = useCallback(
        (variant: MathDialogVariant, latex: string, pos: number) => {
            setMathVariant(variant);
            setMathMode('edit');
            setMathLatex(latex);
            setEditingMathPos(pos);
            setMathDialogOpen(true);
        },
        [],
    );

    const handleMathDialogOpenChange = (open: boolean) => {
        setMathDialogOpen(open);

        if (!open) {
            setEditingMathPos(null);
        }
    };

    const saveMath = useCallback(() => {
        if (!editor || mathLatex.trim() === '') {
            return;
        }

        const latex = mathLatex.trim();

        if (mathMode === 'create') {
            if (mathVariant === 'inline') {
                editor.chain().focus().insertInlineMath({ latex }).run();
            } else {
                editor.chain().focus().insertBlockMath({ latex }).run();
            }
        } else if (editingMathPos !== null) {
            if (mathVariant === 'inline') {
                editor.commands.updateInlineMath({
                    latex,
                    pos: editingMathPos,
                });
            } else {
                editor.commands.updateBlockMath({
                    latex,
                    pos: editingMathPos,
                });
            }
        }

        setMathDialogOpen(false);
        setEditingMathPos(null);
    }, [editor, editingMathPos, mathLatex, mathMode, mathVariant]);

    const removeMath = useCallback(() => {
        if (!editor || editingMathPos === null) {
            return;
        }

        if (mathVariant === 'inline') {
            editor.commands.deleteInlineMath({ pos: editingMathPos });
        } else {
            editor.commands.deleteBlockMath({ pos: editingMathPos });
        }

        setMathDialogOpen(false);
        setEditingMathPos(null);
    }, [editor, editingMathPos, mathVariant]);

    const openImageUploadDialog = useCallback(() => {
        setImageDialogMode('upload');
        setEditingMedia(null);
        setImageDialogOpen(true);
    }, []);

    const openImageEditDialog = useCallback((media: ArticleMedia) => {
        setImageDialogMode('edit');
        setEditingMedia(media);
        setImageDialogOpen(true);
    }, []);

    const openImageEditDialogFromMediaId = useCallback(
        (mediaId: string) => {
            const media = mediaItems.find((item) => item.id === mediaId);

            if (media) {
                openImageEditDialog(media);

                return;
            }

            const selected = editor ? getSelectedArticleImage(editor) : null;

            if (selected?.mediaId === mediaId) {
                openImageEditDialog({
                    id: mediaId,
                    article_id: articleId ?? null,
                    original_filename: '',
                    mime_type: 'image/jpeg',
                    width: 0,
                    height: 0,
                    file_size: 0,
                    alt_text: selected.alt,
                    copyright: selected.copyright,
                    caption: selected.caption,
                    created_at: '',
                    updated_at: '',
                    preview_webp_url: selected.previewWebpUrl,
                    preview_jpeg_url: selected.previewJpegUrl,
                    original_url: '',
                });
            }
        },
        [articleId, editor, mediaItems, openImageEditDialog],
    );

    const handleRemoveSelectedArticleImage = useCallback(() => {
        if (!editor) {
            return;
        }

        deleteSelectedArticleImage(editor);
    }, [editor]);

    const handleImageUpload = useCallback(
        async (file: File, metadata: ArticleMediaFormData) => {
            if (!onMediaUpload) {
                return;
            }

            const media = await onMediaUpload(file, metadata);

            if (editor) {
                insertArticleImage(editor, media);
            }

            setImageDialogOpen(false);
        },
        [editor, onMediaUpload],
    );

    const handleImageMetadataSave = useCallback(
        async (metadata: ArticleMediaFormData) => {
            if (!editingMedia || !onMediaUpdate) {
                return;
            }

            const updated = await onMediaUpdate(editingMedia.id, metadata);

            if (editor) {
                syncArticleImagesFromMedia(editor, [updated]);
            }

            setImageDialogOpen(false);
            setEditingMedia(null);
        },
        [editor, editingMedia, onMediaUpdate],
    );

    const handleMediaDelete = useCallback(
        async (media: ArticleMedia) => {
            if (!onMediaDelete) {
                return;
            }

            const usedIds = editor
                ? getArticleImageMediaIdsFromEditor(editor)
                : [];

            if (usedIds.includes(media.id)) {
                window.alert(t('articles.editor.image_in_use_alert'));

                return;
            }

            await onMediaDelete(media);
        },
        [editor, onMediaDelete, t],
    );

    const getUsedMediaIds = useCallback(() => {
        if (!editor) {
            return [];
        }

        return getArticleImageMediaIdsFromEditor(editor);
    }, [editor]);

    useEffect(() => {
        if (!editor || mediaItems.length === 0) {
            return;
        }

        syncArticleImagesFromMedia(editor, mediaItems);
    }, [editor, mediaItems]);

    const syncFootnoteCount = useCallback(() => {
        if (!editor) {
            return;
        }

        setFootnoteCount(getFootnotesFromEditor(editor).length);
    }, [editor]);

    const openFootnotesSheet = useCallback(
        (footnoteId?: string) => {
            if (footnoteId && editor) {
                const footnote = getFootnoteById(editor, footnoteId);

                if (footnote) {
                    focusFootnoteInEditor(editor, footnote);
                }

                setFocusedFootnoteId(footnoteId);
            } else {
                setFocusedFootnoteId(null);
            }

            setFootnotesSheetOpen(true);
        },
        [editor],
    );

    const handleFootnotesSheetOpenChange = (open: boolean) => {
        setFootnotesSheetOpen(open);

        if (!open) {
            setFocusedFootnoteId(null);
        }
    };

    const closeSpellCheckPopover = useCallback(() => {
        setSpellCheckPopoverMatchId(null);
        setSpellCheckPopoverRect(null);
    }, []);

    const handleSpellCheckMarkClick = useCallback(
        (matchId: string, rect: DOMRect) => {
            setSpellCheckPopoverMatchId(matchId);
            setSpellCheckPopoverRect(rect);
            setFocusedSpellCheckMatchId(matchId);
        },
        [],
    );

    const handleSpellCheckClick = useCallback(async () => {
        if (!editor || isChecking) {
            return;
        }

        closeSpellCheckPopover();
        setSpellCheckSheetOpen(true);

        if (!hasRun) {
            await runCheck(editor);
        }
    }, [closeSpellCheckPopover, editor, hasRun, isChecking, runCheck]);

    const handleStartSpellCheck = useCallback(async () => {
        if (!editor || isChecking) {
            return;
        }

        await runCheck(editor);
    }, [editor, isChecking, runCheck]);

    const handleSpellCheckSheetOpenChange = (open: boolean) => {
        setSpellCheckSheetOpen(open);

        if (!open) {
            setFocusedSpellCheckMatchId(null);
        }
    };

    const handleFocusSpellCheckMatch = useCallback(
        (match: MappedSpellCheckMatch) => {
            setFocusedSpellCheckMatchId(match.id);
        },
        [],
    );

    const openFootnoteDialog = useCallback(
        (footnote?: ArticleFootnote) => {
            if (!editor) {
                return;
            }

            if (footnote) {
                setFootnoteText(footnote.content);
                setFootnoteExcerpt(footnote.excerpt);
                setFootnoteMode('edit');
                setEditingFootnoteId(footnote.id);
                setPendingFootnoteSelection(null);
            } else {
                const { from, to, empty } = editor.state.selection;

                if (empty) {
                    return;
                }

                const existing = getFootnoteAtSelection(editor);
                const bounds = trimSelectionBounds(editor.state.doc, from, to);

                if (!bounds && !existing) {
                    return;
                }

                setPendingFootnoteSelection(bounds ?? { from, to });
                setFootnoteExcerpt(
                    editor.state.doc.textBetween(
                        bounds?.from ?? from,
                        bounds?.to ?? to,
                    ),
                );

                if (existing) {
                    setFootnoteText(existing.content);
                    setFootnoteMode('edit');
                    setEditingFootnoteId(existing.id);
                } else {
                    setFootnoteText('');
                    setFootnoteMode('create');
                    setEditingFootnoteId(null);
                }
            }

            setFootnoteDialogOpen(true);
        },
        [editor],
    );

    const removeFootnote = useCallback(
        (footnote: ArticleFootnote) => {
            if (!editor) {
                return;
            }

            removeFootnoteById(editor, footnote.id);
            setFootnoteDialogOpen(false);
            setEditingFootnoteId(null);

            if (focusedFootnoteId === footnote.id) {
                setFocusedFootnoteId(null);
            }

            syncFootnoteCount();
        },
        [editor, focusedFootnoteId, syncFootnoteCount],
    );

    const removeEditingFootnote = () => {
        if (!editor || !editingFootnoteId) {
            return;
        }

        const footnote = getFootnoteById(editor, editingFootnoteId);

        if (footnote) {
            removeFootnote(footnote);
        }
    };

    const handleFootnoteDialogOpenChange = (open: boolean) => {
        setFootnoteDialogOpen(open);

        if (!open) {
            setEditingFootnoteId(null);
            setPendingFootnoteSelection(null);
        }
    };

    const saveFootnote = () => {
        if (!editor || footnoteText.trim() === '') {
            return;
        }

        if (footnoteMode === 'create') {
            const chain = editor.chain().focus();

            if (pendingFootnoteSelection) {
                chain.setTextSelection(pendingFootnoteSelection);
            }

            chain.setFootnote(footnoteText.trim()).run();
        } else if (editingFootnoteId) {
            editor.commands.updateFootnoteById(
                editingFootnoteId,
                footnoteText.trim(),
            );
        } else {
            editor
                .chain()
                .focus()
                .updateFootnoteContent(footnoteText.trim())
                .run();
        }

        setFootnoteDialogOpen(false);
        setEditingFootnoteId(null);
        setPendingFootnoteSelection(null);
        syncFootnoteCount();
    };

    useEffect(() => {
        setChrome({
            actions: (
                <>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index()} prefetch>
                            <ArrowLeft className="size-4" />
                            {t('articles.editor.back')}
                        </Link>
                    </Button>

                    {status && (
                        <Badge variant="secondary">
                            {getArticleStatusLabel(status, t)}
                        </Badge>
                    )}

                    {workflowActions}

                    <Button
                        variant="ghost"
                        size="sm"
                        type="button"
                        onClick={() => openFootnotesSheet()}
                    >
                        <SquareAsterisk className="size-4" />
                        {t('articles.editor.footnotes')}
                        {footnoteCount > 0 && (
                            <Badge
                                variant="secondary"
                                className="ml-1 h-5 min-w-5 px-1"
                            >
                                {footnoteCount}
                            </Badge>
                        )}
                    </Button>

                    <Button
                        variant="ghost"
                        size="sm"
                        type="button"
                        onClick={() => setMediaSheetOpen(true)}
                    >
                        <Image className="size-4" />
                        {t('articles.editor.media')}
                        {mediaItems.length > 0 && (
                            <Badge
                                variant="secondary"
                                className="ml-1 h-5 min-w-5 px-1"
                            >
                                {mediaItems.length}
                            </Badge>
                        )}
                    </Button>

                    {articleId !== undefined && !readOnly && (
                        <Button
                            variant="ghost"
                            size="sm"
                            type="button"
                            disabled={pdfExporting}
                            onClick={async () => {
                                if (articleId === undefined) {
                                    return;
                                }

                                setPdfExporting(true);

                                try {
                                    const exportContent =
                                        (editor?.getJSON() as
                                            TipTapDocument | undefined) ??
                                        content;

                                    const blob = await generateArticlePdfBlob({
                                        title,
                                        content: exportContent,
                                        editorSettings,
                                        mediaItems,
                                        locale,
                                        footnotesTitle: t(
                                            'articles.editor.footnotes',
                                        ),
                                    });

                                    const formData = new FormData();
                                    formData.append(
                                        'file',
                                        blob,
                                        `${title || 'article'}.pdf`,
                                    );

                                    router.post(
                                        storeArticlePdf.url({
                                            article: articleId,
                                        }),
                                        formData,
                                        {
                                            forceFormData: true,
                                            onFinish: () =>
                                                setPdfExporting(false),
                                            onError: () => {
                                                toast.error(
                                                    t(
                                                        'articles.pdf.export_failed',
                                                    ),
                                                );
                                            },
                                        },
                                    );
                                } catch (error) {
                                    console.error('PDF export failed', error);
                                    setPdfExporting(false);
                                    toast.error(
                                        t('articles.pdf.export_failed'),
                                    );
                                }
                            }}
                        >
                            {pdfExporting ? (
                                <Spinner className="size-4" />
                            ) : (
                                <FileText className="size-4" />
                            )}
                            {pdfExporting
                                ? t('articles.pdf.exporting')
                                : t('articles.pdf.export')}
                        </Button>
                    )}

                    {articleId !== undefined && canManageMetadata && (
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={metadataEdit({ article: articleId })}
                                prefetch
                            >
                                <Tags className="size-4" />
                                {t('articles.editor.metadata')}
                            </Link>
                        </Button>
                    )}

                    {!readOnly && (
                        <Button
                            type="submit"
                            form="article-document-form"
                            size="sm"
                            disabled={processing}
                        >
                            {processing ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Save className="size-4" />
                            )}
                            {t('articles.editor.save')}
                        </Button>
                    )}
                </>
            ),
        });
    }, [
        articleId,
        editor,
        footnoteCount,
        mediaItems.length,
        openFootnoteDialog,
        openFootnotesSheet,
        pdfExporting,
        processing,
        setChrome,
        status,
        workflowActions,
        readOnly,
        canManageMetadata,
        t,
        locale,
        title,
        content,
        editorSettings,
        mediaItems,
    ]);

    useEffect(() => {
        if (!editor) {
            setChrome({ statusBar: null });

            return;
        }

        const syncDocumentStats = () => {
            const stats = getDocumentStats(
                combineDocumentText(title, editor.getText()),
            );

            setChrome({
                statusBar: (
                    <ArticleEditorFooter
                        words={stats.words}
                        letters={stats.letters}
                        articleId={articleId}
                        currentAssignee={currentAssignee}
                        submissionDeadline={submissionDeadline}
                        targetCharacterCount={targetCharacterCount}
                        versionsCount={versions.length}
                        onHistoryClick={() =>
                            setWorkflowHistorySheetOpen(true)
                        }
                        onVersionsClick={() => setVersionSheetOpen(true)}
                    />
                ),
            });
        };

        syncDocumentStats();

        editor.on('update', syncDocumentStats);

        return () => {
            editor.off('update', syncDocumentStats);
        };
    }, [
        articleId,
        currentAssignee,
        editor,
        setChrome,
        submissionDeadline,
        targetCharacterCount,
        title,
        versions.length,
    ]);

    useEffect(() => {
        if (!editor || readOnly) {
            setChrome({ toolbar: null });

            return;
        }

        setChrome({
            toolbar: (
                <TipTapToolbar
                    editor={editor}
                    showMarginalNotes={editorSettings.has_marginal_column}
                    onFootnoteClick={() => openFootnoteDialog()}
                    onImageClick={() => openImageUploadDialog()}
                    onRemoveArticleImage={handleRemoveSelectedArticleImage}
                    onInlineMathClick={() => openMathDialogForCreate('inline')}
                    onBlockMathClick={() => openMathDialogForCreate('block')}
                    onSpellCheckClick={() => {
                        void handleSpellCheckClick();
                    }}
                    isSpellChecking={isChecking}
                />
            ),
        });
    }, [
        editor,
        editorSettings.has_marginal_column,
        handleRemoveSelectedArticleImage,
        handleSpellCheckClick,
        isChecking,
        openFootnoteDialog,
        openImageUploadDialog,
        openMathDialogForCreate,
        readOnly,
        setChrome,
    ]);

    useEffect(() => {
        if (!editor) {
            return;
        }

        // Seed the footnote count once the editor is ready; further updates come from editor events.
        // eslint-disable-next-line react-hooks/set-state-in-effect -- initial sync from TipTap editor state
        syncFootnoteCount();

        editor.on('update', syncFootnoteCount);

        return () => {
            editor.off('update', syncFootnoteCount);
        };
    }, [editor, syncFootnoteCount]);

    useEffect(() => {
        return () => {
            clearChrome();
        };
    }, [clearChrome]);

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        if (readOnly) {
            return;
        }

        const latestContent =
            (editor?.getJSON() as TipTapDocument | undefined) ?? content;

        onContentChange(latestContent);
        onSubmit(latestContent);
    };

    return (
        <>
            <form
                id="article-document-form"
                onSubmit={handleSubmit}
                className="flex min-h-full flex-col"
            >
                <div className="min-h-full flex-1 bg-muted/30 px-6 py-8 md:px-4 md:py-8">
                    <article
                        className={cn(
                            'document-page mx-auto max-w-5xl rounded-sm bg-card px-8 py-12 shadow-md ring-1 ring-border/40 md:px-14 md:py-16',
                            editorSettings.font === 'roboto'
                                ? 'document-font-roboto'
                                : 'document-font-spectral',
                            editorSettings.has_marginal_column &&
                                'document-with-margin',
                        )}
                    >
                        <input
                            id="title"
                            type="text"
                            value={title}
                            onChange={(event) =>
                                onTitleChange(event.target.value)
                            }
                            placeholder={t('articles.editor.title_placeholder')}
                            required
                            readOnly={readOnly}
                            className={cn(
                                'mb-8 w-full border-0 bg-transparent text-3xl font-bold tracking-tight text-foreground placeholder:text-muted-foreground/50 focus:outline-none md:text-4xl',
                                readOnly && 'cursor-default',
                            )}
                        />
                        <InputError className="mb-4" message={errors.title} />

                        <div
                            className={cn(
                                'grid grid-cols-1',
                                editorSettings.has_marginal_column &&
                                    'lg:grid-cols-[minmax(0,1fr)_12rem] lg:gap-8',
                            )}
                        >
                            <TipTapEditor
                                variant="document"
                                content={content}
                                onChange={onContentChange}
                                readOnly={readOnly}
                                onEditorReady={setEditor}
                                onFootnoteMarkClick={openFootnotesSheet}
                                onSpellCheckMarkClick={
                                    handleSpellCheckMarkClick
                                }
                                onArticleImageDoubleClick={
                                    readOnly
                                        ? undefined
                                        : openImageEditDialogFromMediaId
                                }
                                onInlineMathClick={
                                    readOnly
                                        ? undefined
                                        : (latex, pos) =>
                                              openMathDialogForEdit(
                                                  'inline',
                                                  latex,
                                                  pos,
                                              )
                                }
                                onBlockMathClick={
                                    readOnly
                                        ? undefined
                                        : (latex, pos) =>
                                              openMathDialogForEdit(
                                                  'block',
                                                  latex,
                                                  pos,
                                              )
                                }
                            />
                            {editor && editorSettings.has_marginal_column && (
                                <MarginalNotesColumn
                                    editor={editor}
                                    readOnly={readOnly}
                                />
                            )}
                        </div>
                        <InputError className="mt-4" message={errors.content} />
                    </article>
                </div>
            </form>

            {!readOnly && (
                <>
                    <FootnoteDialog
                        open={footnoteDialogOpen}
                        onOpenChange={handleFootnoteDialogOpenChange}
                        value={footnoteText}
                        onChange={setFootnoteText}
                        onSave={saveFootnote}
                        onRemove={
                            footnoteMode === 'edit'
                                ? removeEditingFootnote
                                : undefined
                        }
                        excerpt={footnoteExcerpt}
                        mode={footnoteMode}
                    />

                    <MathDialog
                        open={mathDialogOpen}
                        onOpenChange={handleMathDialogOpenChange}
                        value={mathLatex}
                        onChange={setMathLatex}
                        onSave={saveMath}
                        onRemove={mathMode === 'edit' ? removeMath : undefined}
                        variant={mathVariant}
                        mode={mathMode}
                    />
                </>
            )}

            <Sheet
                open={footnotesSheetOpen}
                onOpenChange={handleFootnotesSheetOpenChange}
            >
                <SheetContent className="w-full sm:max-w-md">
                    <SheetHeader className="border-b border-border/60 pb-4">
                        <SheetTitle>
                            {t('articles.editor.footnotes')}
                        </SheetTitle>
                        <SheetDescription>
                            {t('articles.editor.footnotes_sheet')}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="overflow-y-auto px-4 pt-4 pb-6">
                        <FootnotesPanel
                            editor={editor}
                            onEditFootnote={openFootnoteDialog}
                            onRemoveFootnote={removeFootnote}
                            onFocusFootnote={(footnote) =>
                                setFocusedFootnoteId(footnote.id)
                            }
                            focusedFootnoteId={focusedFootnoteId}
                            canEdit={!readOnly}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <Sheet
                open={spellCheckSheetOpen}
                onOpenChange={handleSpellCheckSheetOpenChange}
            >
                <SheetContent className="w-full sm:max-w-md">
                    <SheetHeader className="border-b border-border/60 pb-4">
                        <SheetTitle>
                            {t('articles.editor.spellcheck')}
                        </SheetTitle>
                        <SheetDescription>
                            {t('articles.editor.spellcheck_sheet')}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="overflow-y-auto px-4 pt-4 pb-6">
                        <SpellCheckPanel
                            editor={editor}
                            hasRun={hasRun}
                            isChecking={isChecking}
                            focusedMatchId={focusedSpellCheckMatchId}
                            onFocusMatch={handleFocusSpellCheckMatch}
                            onStartCheck={() => {
                                void handleStartSpellCheck();
                            }}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <SpellCheckPopover
                editor={editor}
                matchId={spellCheckPopoverMatchId}
                anchorRect={spellCheckPopoverRect}
                onClose={closeSpellCheckPopover}
                onFocusMatch={handleFocusSpellCheckMatch}
            />

            {!readOnly && (
                <ArticleImageDialog
                    open={imageDialogOpen}
                    onOpenChange={setImageDialogOpen}
                    mode={imageDialogMode}
                    media={editingMedia}
                    uploading={mediaUploading}
                    onUpload={handleImageUpload}
                    onSave={handleImageMetadataSave}
                />
            )}

            <Sheet open={mediaSheetOpen} onOpenChange={setMediaSheetOpen}>
                <SheetContent className="w-full sm:max-w-md">
                    <SheetHeader className="border-b border-border/60 pb-4">
                        <SheetTitle>{t('articles.editor.media')}</SheetTitle>
                        <SheetDescription>
                            {t('articles.editor.media_sheet')}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="overflow-y-auto px-4 pt-4 pb-6">
                        <ArticleMediaPanel
                            editor={editor}
                            mediaItems={mediaItems}
                            onUploadClick={openImageUploadDialog}
                            onEditMedia={openImageEditDialog}
                            onDeleteMedia={handleMediaDelete}
                            getUsedMediaIds={getUsedMediaIds}
                            canEdit={!readOnly}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <Sheet
                open={workflowHistorySheetOpen}
                onOpenChange={setWorkflowHistorySheetOpen}
            >
                <SheetContent className="w-full sm:max-w-md">
                    <SheetHeader className="border-b border-border/60 pb-4">
                        <SheetTitle>
                            {t('articles.editor.history')}
                        </SheetTitle>
                        <SheetDescription>
                            {t('articles.editor.history_sheet')}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="overflow-y-auto px-4 pt-4 pb-6">
                        <WorkflowHistoryPanel events={workflowEvents} />
                    </div>
                </SheetContent>
            </Sheet>

            {articleId !== undefined && (
                <Sheet
                    open={versionSheetOpen}
                    onOpenChange={setVersionSheetOpen}
                >
                    <SheetContent
                        className={cn(
                            'w-full',
                            versionView === 'compare'
                                ? 'sm:max-w-4xl'
                                : 'sm:max-w-md',
                        )}
                    >
                        <SheetHeader className="border-b border-border/60 pb-4">
                            <SheetTitle>
                                {t('articles.editor.versions')}
                            </SheetTitle>
                            <SheetDescription>
                                {versionView === 'compare'
                                    ? t('articles.versions.compare_hint')
                                    : t('articles.editor.versions_sheet')}
                            </SheetDescription>
                        </SheetHeader>
                        <div className="overflow-y-auto px-4 pb-6">
                            <div className="flex gap-1 pt-4">
                                <Button
                                    type="button"
                                    variant={
                                        versionView === 'history'
                                            ? 'secondary'
                                            : 'ghost'
                                    }
                                    size="sm"
                                    onClick={() => setVersionView('history')}
                                >
                                    {t('articles.versions.history')}
                                </Button>
                                <Button
                                    type="button"
                                    variant={
                                        versionView === 'compare'
                                            ? 'secondary'
                                            : 'ghost'
                                    }
                                    size="sm"
                                    onClick={() => setVersionView('compare')}
                                >
                                    {t('articles.versions.compare')}
                                </Button>
                            </div>
                            {versionView === 'history' ? (
                                <VersionHistory
                                    articleId={articleId}
                                    versions={versions}
                                    variant="compact"
                                    canRestore={!readOnly}
                                />
                            ) : (
                                <VersionCompare
                                    versions={versions}
                                    editor={editor}
                                    baseId={compareBaseId}
                                    compareId={compareTargetId}
                                    onBaseChange={setCompareBaseId}
                                    onCompareChange={setCompareTargetId}
                                    onNavigateToEditor={() =>
                                        setVersionSheetOpen(false)
                                    }
                                />
                            )}
                        </div>
                    </SheetContent>
                </Sheet>
            )}
        </>
    );
}
