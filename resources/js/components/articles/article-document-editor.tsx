import { Link, router } from '@inertiajs/react';
import type { Editor } from '@tiptap/react';
import {
    ArrowLeft,
    FileText,
    Save,
    Tags,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import ArticleCommentController from '@/actions/App/Http/Controllers/ArticleCommentController';
import ArticleEditorFooter from '@/components/articles/article-editor-footer';
import ArticleImageDialog from '@/components/articles/article-image-dialog';
import ArticleMediaPanel from '@/components/articles/article-media-panel';
import CommentComposerDialog from '@/components/articles/comment-composer-dialog';
import CommentMarginColumn from '@/components/articles/comment-margin-column';
import CommentSelectionButton from '@/components/articles/comment-selection-button';
import CommentsPanel from '@/components/articles/comments-panel';
import EditorSidePanel from '@/components/articles/editor-side-panel';
import FootnoteDialog from '@/components/articles/footnote-dialog';
import FootnotesPanel from '@/components/articles/footnotes-panel';
import MarginalNotesColumn from '@/components/articles/marginal-notes-column';
import MathDialog from '@/components/articles/math-dialog';
import type {
    MathDialogMode,
    MathDialogVariant,
} from '@/components/articles/math-dialog';
import ProofreadPanel from '@/components/articles/proofread-panel';
import ProofreadPopover from '@/components/articles/proofread-popover';
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
import { Spinner } from '@/components/ui/spinner';
import { useArticleEditorChrome } from '@/contexts/article-editor-chrome-context';
import type { ArticleMediaFormData } from '@/hooks/use-article-media';
import { useProofread } from '@/hooks/use-proofread';
import { useSpellCheck } from '@/hooks/use-spell-check';
import { useTranslation } from '@/hooks/use-translation';
import { generateArticlePdfBlob } from '@/lib/article-pdf/generate';
import { getArticleStatusLabel } from '@/lib/article-status';
import { combineDocumentText, getDocumentStats } from '@/lib/document-stats';
import {
    deleteSelectedArticleImage,
    focusCommentThreadInEditor,
    focusFootnoteInEditor,
    getArticleImageMediaIdsFromEditor,
    getCommentThreadIdsInEditor,
    getFootnoteAtSelection,
    getFootnoteById,
    getFootnotesFromEditor,
    getSelectedArticleImage,
    insertArticleImage,
    removeFootnoteById,
    syncArticleImagesFromMedia,
    trimSelectionBounds,
} from '@/lib/tiptap';
import type {
    ArticleFootnote,
    MappedProofreadIssue,
    MappedSpellCheckMatch,
} from '@/lib/tiptap';
import { cn } from '@/lib/utils';
import { index } from '@/routes/articles';
import { edit as metadataEdit } from '@/routes/articles/metadata';
import { store as storeArticlePdf } from '@/routes/articles/pdfs';
import type {
    ArticleCommentThread,
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
    commentThreads?: ArticleCommentThread[];
    canComment?: boolean;
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

type EditorRightPanel =
    | 'spellcheck'
    | 'proofread'
    | 'history'
    | 'versions'
    | 'footnotes'
    | 'media'
    | 'comments';

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
    commentThreads = [],
    canComment = false,
    mediaItems = [],
    mediaUploading = false,
    onMediaUpload,
    onMediaUpdate,
    onMediaDelete,
}: ArticleDocumentEditorProps) {
    const { t, locale } = useTranslation();
    const { setChrome, clearChrome } = useArticleEditorChrome();
    const { isChecking, hasRun, error: spellCheckError, runCheck } = useSpellCheck();
    const {
        isChecking: isProofreading,
        hasRun: proofreadHasRun,
        error: proofreadError,
        runCheck: runProofread,
    } = useProofread();
    const [editor, setEditor] = useState<Editor | null>(null);
    const [footnoteDialogOpen, setFootnoteDialogOpen] = useState(false);
    const [footnoteText, setFootnoteText] = useState('');
    const [footnoteExcerpt, setFootnoteExcerpt] = useState('');
    const [footnoteMode, setFootnoteMode] = useState<'create' | 'edit'>(
        'create',
    );
    const [footnoteCount, setFootnoteCount] = useState(0);
    const [focusedFootnoteId, setFocusedFootnoteId] = useState<string | null>(
        null,
    );
    const [focusedSpellCheckMatchId, setFocusedSpellCheckMatchId] = useState<
        string | null
    >(null);
    const [spellCheckPopoverMatchId, setSpellCheckPopoverMatchId] = useState<
        string | null
    >(null);
    const [spellCheckPopoverRect, setSpellCheckPopoverRect] =
        useState<DOMRect | null>(null);
    const [focusedProofreadIssueId, setFocusedProofreadIssueId] = useState<
        string | null
    >(null);
    const [proofreadPopoverIssueId, setProofreadPopoverIssueId] = useState<
        string | null
    >(null);
    const [proofreadPopoverRect, setProofreadPopoverRect] =
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
    const [mathDialogOpen, setMathDialogOpen] = useState(false);
    const [mathLatex, setMathLatex] = useState('');
    const [mathVariant, setMathVariant] = useState<MathDialogVariant>('inline');
    const [mathMode, setMathMode] = useState<MathDialogMode>('create');
    const [editingMathPos, setEditingMathPos] = useState<number | null>(null);
    const [pdfExporting, setPdfExporting] = useState(false);
    const [versionView, setVersionView] = useState<'history' | 'compare'>(
        'history',
    );
    const [activeRightPanel, setActiveRightPanel] =
        useState<EditorRightPanel | null>(null);
    const [commentsVisible, setCommentsVisible] = useState(true);
    const [activeCommentThreadId, setActiveCommentThreadId] = useState<
        string | null
    >(null);
    const [presentCommentThreadIds, setPresentCommentThreadIds] = useState<
        string[]
    >([]);
    const [commentComposerOpen, setCommentComposerOpen] = useState(false);
    const [commentDraft, setCommentDraft] = useState('');
    const [commentExcerpt, setCommentExcerpt] = useState('');
    const [commentSubmitting, setCommentSubmitting] = useState(false);
    const [pendingCommentSelection, setPendingCommentSelection] = useState<{
        from: number;
        to: number;
    } | null>(null);
    const [compareBaseId, setCompareBaseId] = useState<number | null>(
        () => versions[1]?.id ?? null,
    );
    const [compareTargetId, setCompareTargetId] = useState<number | null>(
        () => versions[0]?.id ?? null,
    );
    const activeRightPanelRef = useRef<EditorRightPanel | null>(null);

    useEffect(() => {
        activeRightPanelRef.current = activeRightPanel;
    }, [activeRightPanel]);

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

    const clearRightPanelFocus = useCallback((panel: EditorRightPanel) => {
        if (panel === 'spellcheck') {
            setFocusedSpellCheckMatchId(null);
        }

        if (panel === 'proofread') {
            setFocusedProofreadIssueId(null);
        }

        if (panel === 'footnotes') {
            setFocusedFootnoteId(null);
        }
    }, []);

    const openRightPanel = useCallback((panel: EditorRightPanel) => {
        setActiveRightPanel(panel);

        if (panel !== 'spellcheck') {
            setFocusedSpellCheckMatchId(null);
        }

        if (panel !== 'proofread') {
            setFocusedProofreadIssueId(null);
        }

        if (panel !== 'footnotes') {
            setFocusedFootnoteId(null);
        }
    }, []);

    const toggleRightPanel = useCallback(
        (panel: EditorRightPanel) => {
            const isOpen = activeRightPanelRef.current === panel;

            if (isOpen) {
                setActiveRightPanel(null);
                clearRightPanelFocus(panel);

                return;
            }

            openRightPanel(panel);
        },
        [clearRightPanelFocus, openRightPanel],
    );

    const openFootnotesSheet = useCallback(
        (footnoteId?: string) => {
            if (footnoteId && editor) {
                const footnote = getFootnoteById(editor, footnoteId);

                if (footnote) {
                    focusFootnoteInEditor(editor, footnote);
                }

                setFocusedFootnoteId(footnoteId);
                openRightPanel('footnotes');

                return;
            }

            setFocusedFootnoteId(null);
            toggleRightPanel('footnotes');
        },
        [editor, openRightPanel, toggleRightPanel],
    );

    const handleRightPanelOpenChange = useCallback(
        (panel: EditorRightPanel, open: boolean) => {
            if (open) {
                openRightPanel(panel);

                return;
            }

            setActiveRightPanel((current) =>
                current === panel ? null : current,
            );

            clearRightPanelFocus(panel);
        },
        [clearRightPanelFocus, openRightPanel],
    );

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
        openRightPanel('spellcheck');

        if (!hasRun) {
            await runCheck(editor);
        }
    }, [closeSpellCheckPopover, editor, hasRun, isChecking, openRightPanel, runCheck]);

    const handleStartSpellCheck = useCallback(async () => {
        if (!editor || isChecking) {
            return;
        }

        await runCheck(editor);
    }, [editor, isChecking, runCheck]);

    const handleFocusSpellCheckMatch = useCallback(
        (match: MappedSpellCheckMatch) => {
            setFocusedSpellCheckMatchId(match.id);
        },
        [],
    );

    const closeProofreadPopover = useCallback(() => {
        setProofreadPopoverIssueId(null);
        setProofreadPopoverRect(null);
    }, []);

    const handleProofreadMarkClick = useCallback(
        (issueId: string, rect: DOMRect) => {
            setProofreadPopoverIssueId(issueId);
            setProofreadPopoverRect(rect);
            setFocusedProofreadIssueId(issueId);
        },
        [],
    );

    const handleProofreadClick = useCallback(async () => {
        if (!editor || isProofreading) {
            return;
        }

        closeProofreadPopover();
        openRightPanel('proofread');

        if (!proofreadHasRun) {
            await runProofread(editor);
        }
    }, [
        closeProofreadPopover,
        editor,
        isProofreading,
        openRightPanel,
        proofreadHasRun,
        runProofread,
    ]);

    const handleStartProofread = useCallback(async () => {
        if (!editor || isProofreading) {
            return;
        }

        await runProofread(editor);
    }, [editor, isProofreading, runProofread]);

    const handleFocusProofreadIssue = useCallback(
        (issue: MappedProofreadIssue) => {
            setFocusedProofreadIssueId(issue.id);
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

    const syncPresentCommentThreadIds = useCallback(() => {
        if (!editor) {
            return;
        }

        setPresentCommentThreadIds(getCommentThreadIdsInEditor(editor));
    }, [editor]);

    useEffect(() => {
        if (!editor) {
            return;
        }

        // eslint-disable-next-line react-hooks/set-state-in-effect -- initial sync from TipTap editor state
        syncPresentCommentThreadIds();

        editor.on('update', syncPresentCommentThreadIds);

        return () => {
            editor.off('update', syncPresentCommentThreadIds);
        };
    }, [editor, syncPresentCommentThreadIds]);

    useEffect(() => {
        if (!editor) {
            return;
        }

        const resolvedThreadIds = commentThreads
            .filter((thread) => thread.resolved_at !== null)
            .map((thread) => thread.id);

        editor.commands.setCommentHighlightState({
            activeThreadId: activeCommentThreadId,
            resolvedThreadIds,
            visible: commentsVisible,
        });
    }, [editor, commentThreads, activeCommentThreadId, commentsVisible]);

    const openCommentComposer = useCallback(() => {
        if (!editor) {
            return;
        }

        const { from, to, empty } = editor.state.selection;

        if (empty) {
            return;
        }

        const bounds = trimSelectionBounds(editor.state.doc, from, to);

        setPendingCommentSelection(bounds ?? { from, to });
        setCommentExcerpt(
            editor.state.doc.textBetween(
                bounds?.from ?? from,
                bounds?.to ?? to,
            ),
        );
        setCommentDraft('');
        setCommentComposerOpen(true);
    }, [editor]);

    const saveComment = useCallback(() => {
        if (
            !editor ||
            articleId === undefined ||
            commentDraft.trim() === '' ||
            commentSubmitting
        ) {
            return;
        }

        const threadId = crypto.randomUUID();
        const chain = editor.chain().focus();

        if (pendingCommentSelection) {
            chain.setTextSelection(pendingCommentSelection);
        }

        chain.setMark('comment', { threadId }).run();

        const content = editor.getJSON() as TipTapDocument;
        onContentChange(content);
        setCommentSubmitting(true);

        router.post(
            ArticleCommentController.store.url({ article: articleId }),
            {
                id: threadId,
                body: commentDraft.trim(),
                anchor_text: commentExcerpt.slice(0, 500),
                content,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setCommentComposerOpen(false);
                    setCommentDraft('');
                    setPendingCommentSelection(null);
                    setActiveCommentThreadId(threadId);
                    openRightPanel('comments');
                },
                onError: () => {
                    editor.commands.removeCommentThreadById(threadId);
                },
                onFinish: () => setCommentSubmitting(false),
            },
        );
    }, [
        articleId,
        commentDraft,
        commentExcerpt,
        commentSubmitting,
        editor,
        onContentChange,
        openRightPanel,
        pendingCommentSelection,
    ]);

    const handleSelectCommentThread = useCallback(
        (threadId: string) => {
            setActiveCommentThreadId(threadId);
            openRightPanel('comments');

            if (editor) {
                focusCommentThreadInEditor(editor, threadId);
            }
        },
        [editor, openRightPanel],
    );

    const handleCommentMarkClick = useCallback(
        (threadId: string) => {
            if (!commentsVisible) {
                return;
            }

            handleSelectCommentThread(threadId);
        },
        [commentsVisible, handleSelectCommentThread],
    );

    const handleCommentComposerOpenChange = useCallback((open: boolean) => {
        setCommentComposerOpen(open);

        if (!open) {
            setPendingCommentSelection(null);
        }
    }, []);

    const unresolvedCommentsCount = commentThreads.filter(
        (thread) => thread.resolved_at === null,
    ).length;

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
                        footnoteCount={footnoteCount}
                        mediaCount={mediaItems.length}
                        commentsCount={unresolvedCommentsCount}
                        showComments={canComment}
                        onFootnotesClick={() => openFootnotesSheet()}
                        onMediaClick={() => toggleRightPanel('media')}
                        onHistoryClick={() => toggleRightPanel('history')}
                        onVersionsClick={() => toggleRightPanel('versions')}
                        onCommentsClick={() => toggleRightPanel('comments')}
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
        footnoteCount,
        mediaItems.length,
        canComment,
        unresolvedCommentsCount,
        openFootnotesSheet,
        openRightPanel,
        toggleRightPanel,
    ]);

    useEffect(() => {
        if (!editor) {
            setChrome({ toolbar: null });

            return;
        }

        if (readOnly) {
            setChrome({
                toolbar: canComment ? (
                    <CommentSelectionButton
                        editor={editor}
                        onClick={openCommentComposer}
                    />
                ) : null,
            });

            return;
        }

        setChrome({
            toolbar: (
                <TipTapToolbar
                    editor={editor}
                    showMarginalNotes={editorSettings.has_marginal_column}
                    onFootnoteClick={() => openFootnoteDialog()}
                    onCommentClick={openCommentComposer}
                    canComment={canComment}
                    onImageClick={() => openImageUploadDialog()}
                    onRemoveArticleImage={handleRemoveSelectedArticleImage}
                    onInlineMathClick={() => openMathDialogForCreate('inline')}
                    onBlockMathClick={() => openMathDialogForCreate('block')}
                    onSpellCheckClick={() => {
                        void handleSpellCheckClick();
                    }}
                    isSpellChecking={isChecking}
                    onProofreadClick={() => {
                        void handleProofreadClick();
                    }}
                    isProofreading={isProofreading}
                />
            ),
        });
    }, [
        canComment,
        editor,
        editorSettings.has_marginal_column,
        handleRemoveSelectedArticleImage,
        handleSpellCheckClick,
        isChecking,
        handleProofreadClick,
        isProofreading,
        openCommentComposer,
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
            <div className="flex min-h-full flex-col items-stretch sm:h-[calc(100dvh-11rem)] sm:min-h-0 sm:flex-row sm:overflow-hidden">
                <form
                    id="article-document-form"
                    onSubmit={handleSubmit}
                    className="flex min-h-full min-w-0 flex-1 flex-col sm:overflow-y-auto"
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
                            <div className="relative min-w-0">
                            <TipTapEditor
                                variant="document"
                                content={content}
                                onChange={onContentChange}
                                readOnly={readOnly}
                                onEditorReady={setEditor}
                                onFootnoteMarkClick={openFootnotesSheet}
                                onCommentMarkClick={handleCommentMarkClick}
                                onSpellCheckMarkClick={
                                    handleSpellCheckMarkClick
                                }
                                onProofreadMarkClick={handleProofreadMarkClick}
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
                            {editor && canComment && (
                                <CommentMarginColumn
                                    editor={editor}
                                    threads={commentThreads}
                                    activeThreadId={activeCommentThreadId}
                                    visible={commentsVisible}
                                    onSelectThread={handleSelectCommentThread}
                                />
                            )}
                            </div>
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

                <EditorSidePanel
                    open={activeRightPanel === 'footnotes'}
                    onOpenChange={(open) =>
                        handleRightPanelOpenChange('footnotes', open)
                    }
                    title={t('articles.editor.footnotes')}
                    description={t('articles.editor.footnotes_sheet')}
                >
                    <div className="px-4 pt-4 pb-6">
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
                </EditorSidePanel>

                <EditorSidePanel
                    open={activeRightPanel === 'media'}
                    onOpenChange={(open) =>
                        handleRightPanelOpenChange('media', open)
                    }
                    title={t('articles.editor.media')}
                    description={t('articles.editor.media_sheet')}
                >
                    <div className="px-4 pt-4 pb-6">
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
                </EditorSidePanel>

                <EditorSidePanel
                    open={activeRightPanel === 'spellcheck'}
                    onOpenChange={(open) =>
                        handleRightPanelOpenChange('spellcheck', open)
                    }
                    title={t('articles.editor.spellcheck')}
                    description={t('articles.editor.spellcheck_sheet')}
                >
                    <div className="px-4 pt-4 pb-6">
                        <SpellCheckPanel
                            editor={editor}
                            hasRun={hasRun}
                            isChecking={isChecking}
                            error={spellCheckError}
                            focusedMatchId={focusedSpellCheckMatchId}
                            onFocusMatch={handleFocusSpellCheckMatch}
                            onStartCheck={() => {
                                void handleStartSpellCheck();
                            }}
                        />
                    </div>
                </EditorSidePanel>

                <EditorSidePanel
                    open={activeRightPanel === 'proofread'}
                    onOpenChange={(open) =>
                        handleRightPanelOpenChange('proofread', open)
                    }
                    title={t('articles.editor.proofread')}
                    description={t('articles.editor.proofread_sheet')}
                >
                    <div className="px-4 pt-4 pb-6">
                        <ProofreadPanel
                            editor={editor}
                            hasRun={proofreadHasRun}
                            isChecking={isProofreading}
                            error={proofreadError}
                            focusedIssueId={focusedProofreadIssueId}
                            onFocusIssue={handleFocusProofreadIssue}
                            onStartCheck={() => {
                                void handleStartProofread();
                            }}
                        />
                    </div>
                </EditorSidePanel>

                <EditorSidePanel
                    open={activeRightPanel === 'history'}
                    onOpenChange={(open) =>
                        handleRightPanelOpenChange('history', open)
                    }
                    title={t('articles.editor.history')}
                    description={t('articles.editor.history_sheet')}
                >
                    <div className="px-4 pt-4 pb-6">
                        <WorkflowHistoryPanel events={workflowEvents} />
                    </div>
                </EditorSidePanel>

                {articleId !== undefined && (
                    <EditorSidePanel
                        open={activeRightPanel === 'versions'}
                        onOpenChange={(open) =>
                            handleRightPanelOpenChange('versions', open)
                        }
                        title={t('articles.editor.versions')}
                        description={
                            versionView === 'compare'
                                ? t('articles.versions.compare_hint')
                                : t('articles.editor.versions_sheet')
                        }
                        className={
                            versionView === 'compare'
                                ? 'sm:w-[56rem]'
                                : undefined
                        }
                    >
                        <div className="px-4 pb-6">
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
                                        setActiveRightPanel(null)
                                    }
                                />
                            )}
                        </div>
                    </EditorSidePanel>
                )}

                {articleId !== undefined && canComment && (
                    <EditorSidePanel
                        open={activeRightPanel === 'comments'}
                        onOpenChange={(open) =>
                            handleRightPanelOpenChange('comments', open)
                        }
                        title={t('articles.editor.comments')}
                        description={t('articles.editor.comments_sheet')}
                    >
                        <div className="px-4 pt-4 pb-6">
                            <CommentsPanel
                                articleId={articleId}
                                threads={commentThreads}
                                presentThreadIds={presentCommentThreadIds}
                                activeThreadId={activeCommentThreadId}
                                onSelectThread={handleSelectCommentThread}
                                commentsVisible={commentsVisible}
                                onCommentsVisibleChange={setCommentsVisible}
                                canComment={canComment}
                            />
                        </div>
                    </EditorSidePanel>
                )}
            </div>

            {canComment && articleId !== undefined && (
                <CommentComposerDialog
                    open={commentComposerOpen}
                    onOpenChange={handleCommentComposerOpenChange}
                    value={commentDraft}
                    onChange={setCommentDraft}
                    onSave={saveComment}
                    excerpt={commentExcerpt}
                    submitting={commentSubmitting}
                />
            )}

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

            <SpellCheckPopover
                editor={editor}
                matchId={spellCheckPopoverMatchId}
                anchorRect={spellCheckPopoverRect}
                onClose={closeSpellCheckPopover}
                onFocusMatch={handleFocusSpellCheckMatch}
            />

            <ProofreadPopover
                editor={editor}
                issueId={proofreadPopoverIssueId}
                anchorRect={proofreadPopoverRect}
                onClose={closeProofreadPopover}
                onFocusIssue={handleFocusProofreadIssue}
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

        </>
    );
}
