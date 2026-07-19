import type { JSONContent } from '@tiptap/core';
import type { EditorSettingsSet } from './editor-settings-set';
import type {
    PublicationCategory,
    PublicationChapter,
    PublicationIssue,
} from './publication';

export type TipTapDocument = JSONContent;

export type TipTapNode = JSONContent;

export type ArticleStatus =
    | 'planned'
    | 'authoring'
    | 'manuscript_submitted'
    | 'product_manager_correction'
    | 'revision_requested'
    | 'revision'
    | 'editorial_work'
    | 'ready_for_publication'
    | 'published';

export type ArticleWorkflowAction =
    | 'submit_manuscript'
    | 'complete_editorial_work'
    | 'force_status'
    | 'request_revision'
    | 'assign_author'
    | 'assign_editorial'
    | 'recall'
    | 'mark_ready'
    | 'publish'
    | 'start_product_manager_correction'
    | 'complete_product_manager_correction';

export type ArticleCapabilities = {
    update_content: boolean;
    submit_manuscript: boolean;
    complete_editorial_work: boolean;
    force_status: boolean;
    request_revision: boolean;
    manage_workflow: boolean;
    delete: boolean;
    comment: boolean;
};

export type ArticleComment = {
    id: number;
    body: string;
    created_at: string;
    user: ArticleUser;
};

export type ArticleCommentThread = {
    id: string;
    anchor_text: string | null;
    resolved_at: string | null;
    resolved_by: ArticleUser | null;
    created_by: ArticleUser;
    created_at: string;
    comments: ArticleComment[];
};

export type ArticleWorkflowUserRole = 'author' | 'editor' | 'lector';

export type ArticleWorkflowUser = {
    id: number;
    name: string;
    role: ArticleWorkflowUserRole;
};

export type Article = {
    id: number;
    title: string;
    content: TipTapDocument | null;
    owner_id: number;
    product_manager_id: number | null;
    author_id: number | null;
    current_assignee_id: number | null;
    status: ArticleStatus;
    publication_issue_id: number | null;
    publication_chapter_id: number | null;
    position: number;
    editor_settings_set_id: number | null;
    submission_deadline: string | null;
    target_character_count: number | null;
    current_character_count?: number;
    published_at: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    author?: ArticleUser | null;
    current_assignee?: ArticleUser | null;
    publication_issue?: PublicationIssue | null;
    publication_chapter?: PublicationChapter | null;
    editorSettingsSet?: EditorSettingsSet | null;
    publication_categories?: PublicationCategory[];
    versions?: ArticleVersion[];
    media?: ArticleMedia[];
};

export type ArticleUser = {
    id: number;
    name: string;
};

export type ArticleMedia = {
    id: string;
    article_id: number | null;
    original_filename: string;
    mime_type: string;
    width: number;
    height: number;
    file_size: number;
    alt_text: string;
    copyright: string;
    caption: string | null;
    created_at: string;
    updated_at: string;
    preview_webp_url: string;
    preview_jpeg_url: string;
    original_url: string;
};

export type ArticleVersion = {
    id: number;
    article_id: number;
    version_number: number;
    title: string;
    content: TipTapDocument | null;
    status: ArticleStatus | null;
    created_by_id: number;
    created_at: string;
    created_by?: {
        id: number;
        name: string;
    };
};

export type ArticleWorkflowEvent = {
    id: number;
    from_status: ArticleStatus | null;
    to_status: ArticleStatus;
    reason: string | null;
    created_at: string;
    actor: ArticleUser;
    assignee: ArticleUser | null;
};

export type ArticlePdf = {
    id: string;
    article_id: number;
    kind: 'generated' | 'annotated';
    parent_pdf_id: string | null;
    article_version_number: number | null;
    title: string;
    created_at: string;
    updated_at: string;
    file_url: string;
    view_url: string;
};

export type PaginatedArticles = {
    data: Article[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type ArticleSortColumn =
    | 'title'
    | 'status'
    | 'publication'
    | 'assignee'
    | 'deadline'
    | 'updated_at';

export type ArticleSortDirection = 'asc' | 'desc';

export type ArticlePerPage = 10 | 15 | 25 | 50;

export type ArticleFilters = {
    search: string | null;
    sort: ArticleSortColumn | null;
    direction: ArticleSortDirection | null;
    publication_id: number | null;
    issue_id: number | null;
    author_id: number | null;
    per_page: number;
};

export type ArticleFilterOptions = {
    publications: Array<{ id: number; name: string }>;
    issues: Array<{ id: number; label: string; publication_id: number }>;
    authors: Array<{ id: number; name: string }>;
};

export const emptyTipTapDocument = (): TipTapDocument => ({
    type: 'doc',
    content: [{ type: 'paragraph' }],
});

export const sampleTipTapContent = (): TipTapDocument => ({
    type: 'doc',
    content: [
        {
            type: 'paragraph',
            content: [{ type: 'text', text: 'Hello' }],
        },
    ],
});
