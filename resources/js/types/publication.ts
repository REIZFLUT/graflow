export type PublicationIssue = {
    id: number;
    publication_id: number;
    label: string;
    created_at: string;
    updated_at: string;
    publication?: Publication;
};

export type PublicationEditorFont = 'spectral' | 'roboto';

export type PublicationEditorSettings = {
    font: PublicationEditorFont;
    has_marginal_column: boolean;
};

export const defaultPublicationEditorSettings: PublicationEditorSettings = {
    font: 'spectral',
    has_marginal_column: true,
};

export type PublicationCategory = {
    id: number;
    publication_id: number;
    name: string;
    created_at: string;
    updated_at: string;
};

export type Publication = {
    id: number;
    name: string;
    editor_settings_set_id: number | null;
    owner_id: number;
    created_at: string;
    updated_at: string;
    owner?: {
        id: number;
        name: string;
    };
    can_edit?: boolean;
    editor_settings_set?: import('./editor-settings-set').EditorSettingsSet;
    issues?: PublicationIssue[];
    categories?: PublicationCategory[];
    issues_count?: number;
};

export type PaginatedPublications = {
    data: Publication[];
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

export function formatPublicationAssignment(
    issue: PublicationIssue | null | undefined,
    t: import('@/lib/i18n').TranslateFn,
): string | null {
    if (!issue) {
        return null;
    }

    const publicationName = issue.publication?.name;

    if (publicationName) {
        return t('articles.assignment.with_publication', {
            publication: publicationName,
            issue: issue.label,
        });
    }

    return t('articles.assignment.issue_only', { issue: issue.label });
}
