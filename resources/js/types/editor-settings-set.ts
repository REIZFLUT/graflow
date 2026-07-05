import type { PublicationEditorFont } from './publication';

export type EditorSettingsSet = {
    id: number;
    name: string;
    font: PublicationEditorFont;
    has_marginal_column: boolean;
    owner_id: number;
    created_at: string;
    updated_at: string;
    publications_count?: number;
    articles_count?: number;
};

export type PaginatedEditorSettingsSets = {
    data: EditorSettingsSet[];
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

export function formatEditorSettingsSetSummary(
    set: Pick<EditorSettingsSet, 'font' | 'has_marginal_column'>,
    t: import('@/lib/i18n').TranslateFn,
): string {
    const fontLabel =
        set.font === 'roboto'
            ? t('editor.summary.roboto')
            : t('editor.summary.spectral');
    const marginLabel = set.has_marginal_column
        ? t('editor.summary.with_marginal')
        : t('editor.summary.without_marginal');

    return t('editor.summary.format', { font: fontLabel, margin: marginLabel });
}
