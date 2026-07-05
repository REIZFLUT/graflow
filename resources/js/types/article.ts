import type { JSONContent } from '@tiptap/core';
import type { EditorSettingsSet } from './editor-settings-set';
import type { PublicationCategory, PublicationIssue } from './publication';

export type TipTapDocument = JSONContent;

export type TipTapNode = JSONContent;

export type Article = {
    id: number;
    title: string;
    content: TipTapDocument | null;
    owner_id: number;
    status: string;
    publication_issue_id: number | null;
    editor_settings_set_id: number | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    publication_issue?: PublicationIssue | null;
    editorSettingsSet?: EditorSettingsSet | null;
    publication_categories?: PublicationCategory[];
    versions?: ArticleVersion[];
    media?: ArticleMedia[];
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
    created_by_id: number;
    created_at: string;
    created_by?: {
        id: number;
        name: string;
    };
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
