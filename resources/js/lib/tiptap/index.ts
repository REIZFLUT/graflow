import { BlockMath, InlineMath } from '@tiptap/extension-mathematics';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { TableKit } from '@tiptap/extension-table/kit';
import UniqueID from '@tiptap/extension-unique-id';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import StarterKit from '@tiptap/starter-kit';
import { ArticleImage } from '@/lib/tiptap/article-image';
import { CharacterFormat } from '@/lib/tiptap/character-format';
import { CommentHighlight } from '@/lib/tiptap/comment-highlight';
import { CommentMark } from '@/lib/tiptap/comment-mark';
import { FootnoteMark } from '@/lib/tiptap/footnote-mark';
import { InfoBox } from '@/lib/tiptap/info-box';
import { MarginalNote } from '@/lib/tiptap/marginal-note';
import { ParagraphFormat } from '@/lib/tiptap/paragraph-format';
import { SpellCheck } from '@/lib/tiptap/spellcheck';
import { VersionDiffHighlight } from '@/lib/tiptap/version-diff-highlight';

export {
    ArticleImage,
    type ArticleImageAttributes,
} from '@/lib/tiptap/article-image';
export {
    deleteSelectedArticleImage,
    getArticleImageMediaIdsFromEditor,
    getArticleImagePosFromFigure,
    getSelectedArticleImage,
    insertArticleImage,
    selectArticleImageFigure,
    syncArticleImagesFromMedia,
} from '@/lib/tiptap/article-image-utils';

export {
    MarginalNote,
    MARGINAL_NOTE_BLOCK_TYPES,
} from '@/lib/tiptap/marginal-note';
export {
    createBlockElements,
    createCharacterFormats,
    createNormalCharacterFormat,
    createNormalParagraphFormat,
    createParagraphFormats,
    NORMAL_FORMAT_ID,
    type BlockElementDefinition,
    type SpecialFormatDefinition,
} from '@/lib/tiptap/special-format-definitions';
export {
    ParagraphFormat,
    getParagraphAtSelection,
} from '@/lib/tiptap/paragraph-format';
export { CharacterFormat } from '@/lib/tiptap/character-format';
export { CommentMark } from '@/lib/tiptap/comment-mark';
export {
    CommentHighlight,
    commentHighlightPluginKey,
    type CommentHighlightState,
} from '@/lib/tiptap/comment-highlight';
export {
    focusCommentThreadInEditor,
    getCommentMarkRange,
    getCommentThreadIdsInEditor,
} from '@/lib/tiptap/comment-utils';
export { InfoBox } from '@/lib/tiptap/info-box';
export {
    getTopLevelBlockAtSelection,
    getTopLevelBlocksWithMarginalNotes,
    setMarginalNoteAtPosition,
} from '@/lib/tiptap/block-utils';
export {
    getFootnoteAtSelection,
    getFootnoteById,
    getFootnotesFromEditor,
    focusFootnoteInEditor,
    removeFootnoteById,
    trimSelectionBounds,
    type ArticleFootnote,
} from '@/lib/tiptap/footnote-utils';
export {
    SpellCheck,
    focusSpellCheckMatch,
    getSpellCheckMatchById,
    getSpellCheckMatches,
    spellCheckPluginKey,
    type MappedSpellCheckMatch,
} from '@/lib/tiptap/spellcheck';
export {
    extractPlainTextWithMap,
    mapMatchesToPositions,
    type LanguageToolMatch,
} from '@/lib/tiptap/spellcheck-utils';
export {
    scrollEditorToPlainTextLine,
    scrollEditorToTitle,
} from '@/lib/tiptap/editor-navigation';

export function createArticleEditorExtensions(options: {
    placeholder: string;
    onInlineMathClick?: (node: ProseMirrorNode, pos: number) => void;
    onBlockMathClick?: (node: ProseMirrorNode, pos: number) => void;
}) {
    return [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
        UniqueID.configure({
            types: [
                'paragraph',
                'heading',
                'blockquote',
                'bulletList',
                'orderedList',
                'infoBox',
            ],
        }),
        Superscript,
        Subscript,
        InlineMath.configure({
            katexOptions: {
                throwOnError: false,
            },
            onClick: options.onInlineMathClick,
        }),
        BlockMath.configure({
            katexOptions: {
                throwOnError: false,
                displayMode: true,
            },
            onClick: options.onBlockMathClick,
        }),
        TableKit.configure({
            table: {
                resizable: true,
            },
        }),
        MarginalNote,
        ParagraphFormat,
        CharacterFormat,
        InfoBox,
        FootnoteMark,
        CommentMark,
        CommentHighlight,
        ArticleImage,
        SpellCheck,
        VersionDiffHighlight,
        Placeholder.configure({
            placeholder: options.placeholder,
        }),
    ];
}
