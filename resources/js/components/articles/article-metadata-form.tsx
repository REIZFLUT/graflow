import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import SearchableMultiSelect from '@/components/ui/searchable-multi-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { create as createEditorSettingsSet } from '@/routes/editor-settings-sets';
import { edit } from '@/routes/publications';
import { formatEditorSettingsSetSummary } from '@/types';
import type {
    EditorSettingsSet,
    Publication,
    PublicationCategory,
    PublicationIssue,
} from '@/types';

type ArticleMetadataFormProps = {
    publications: Publication[];
    assignedPublicationId?: number | null;
    publicationIssueId: number | null;
    onPublicationIssueIdChange: (issueId: number | null) => void;
    publicationCategoryIds: number[];
    onPublicationCategoryIdsChange: (categoryIds: number[]) => void;
    editorSettingsSets: EditorSettingsSet[];
    editorSettingsSetId: number | null;
    onEditorSettingsSetIdChange: (setId: number | null) => void;
    defaultEditorSettingsSet: EditorSettingsSet | null;
    errors: {
        publication_issue_id?: string;
        publication_category_ids?: string;
        editor_settings_set_id?: string;
    };
};

const NONE_VALUE = '__none__';

export default function ArticleMetadataForm({
    publications,
    assignedPublicationId = null,
    publicationIssueId,
    onPublicationIssueIdChange,
    publicationCategoryIds,
    onPublicationCategoryIdsChange,
    editorSettingsSets,
    editorSettingsSetId,
    onEditorSettingsSetIdChange,
    defaultEditorSettingsSet,
    errors,
}: ArticleMetadataFormProps) {
    const { t } = useTranslation();
    const { can } = usePage().props;
    const canManageEditorSettingsSets = can.manageEditorSettingsSets;

    const initialPublicationId = useMemo(() => {
        if (!publicationIssueId) {
            return null;
        }

        for (const publication of publications) {
            if (
                publication.issues?.some(
                    (issue) => issue.id === publicationIssueId,
                )
            ) {
                return publication.id;
            }
        }

        return assignedPublicationId;
    }, [assignedPublicationId, publicationIssueId, publications]);

    const [selectedPublicationId, setSelectedPublicationId] = useState<
        number | null
    >(initialPublicationId);

    useEffect(() => {
        setSelectedPublicationId(initialPublicationId);
    }, [initialPublicationId]);

    const availableIssues: PublicationIssue[] = useMemo(() => {
        if (!selectedPublicationId) {
            return [];
        }

        const publication = publications.find(
            (item) => item.id === selectedPublicationId,
        );

        return publication?.issues ?? [];
    }, [publications, selectedPublicationId]);

    const availableCategories: PublicationCategory[] = useMemo(() => {
        if (!selectedPublicationId) {
            return [];
        }

        const publication = publications.find(
            (item) => item.id === selectedPublicationId,
        );

        return publication?.categories ?? [];
    }, [publications, selectedPublicationId]);

    const filterCategoryIdsForPublication = (
        publicationId: number | null,
        categoryIds: number[],
    ): number[] => {
        if (!publicationId) {
            return [];
        }

        const publication = publications.find(
            (item) => item.id === publicationId,
        );
        const validIds = new Set(
            publication?.categories?.map((category) => category.id) ?? [],
        );

        return categoryIds.filter((id) => validIds.has(id));
    };

    const handlePublicationChange = (value: string) => {
        if (value === NONE_VALUE) {
            setSelectedPublicationId(null);
            onPublicationIssueIdChange(null);
            onPublicationCategoryIdsChange([]);

            return;
        }

        const publicationId = Number(value);
        setSelectedPublicationId(publicationId);

        const publication = publications.find(
            (item) => item.id === publicationId,
        );
        const issueStillValid = publication?.issues?.some(
            (issue) => issue.id === publicationIssueId,
        );

        if (!issueStillValid) {
            onPublicationIssueIdChange(null);
        }

        onPublicationCategoryIdsChange(
            filterCategoryIdsForPublication(
                publicationId,
                publicationCategoryIds,
            ),
        );
    };

    const handleIssueChange = (value: string) => {
        if (value === NONE_VALUE) {
            onPublicationIssueIdChange(null);
            onPublicationCategoryIdsChange([]);

            return;
        }

        onPublicationIssueIdChange(Number(value));
    };

    const categoryOptions = availableCategories.map((category) => ({
        value: category.id,
        label: category.name,
    }));

    return (
        <div className="space-y-8">
            <section className="space-y-4">
                <div>
                    <h3 className="text-base font-medium">
                        {t('articles.metadata.publication.heading')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t('articles.metadata.publication.description')}
                    </p>
                </div>

                <div className="grid max-w-lg gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="publication">
                            {t('articles.metadata.publication.label')}
                        </Label>
                        <Select
                            value={
                                selectedPublicationId
                                    ? String(selectedPublicationId)
                                    : NONE_VALUE
                            }
                            onValueChange={handlePublicationChange}
                        >
                            <SelectTrigger
                                id="publication"
                                className="w-full"
                            >
                                <SelectValue
                                    placeholder={t(
                                        'articles.metadata.publication.placeholder',
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE_VALUE}>
                                    {t('common.none_assignment')}
                                </SelectItem>
                                {publications.map((publication) => (
                                    <SelectItem
                                        key={publication.id}
                                        value={String(publication.id)}
                                    >
                                        {publication.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="issue">
                            {t('articles.metadata.issue.label')}
                        </Label>
                        <Select
                            value={
                                publicationIssueId
                                    ? String(publicationIssueId)
                                    : NONE_VALUE
                            }
                            onValueChange={handleIssueChange}
                            disabled={
                                !selectedPublicationId ||
                                availableIssues.length === 0
                            }
                        >
                            <SelectTrigger id="issue" className="w-full">
                                <SelectValue
                                    placeholder={
                                        selectedPublicationId
                                            ? t(
                                                  'articles.metadata.issue.placeholder',
                                              )
                                            : t(
                                                  'articles.metadata.issue.placeholder_no_publication',
                                              )
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE_VALUE}>
                                    {t('common.none_assignment')}
                                </SelectItem>
                                {availableIssues.map((issue) => (
                                    <SelectItem
                                        key={issue.id}
                                        value={String(issue.id)}
                                    >
                                        {issue.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {selectedPublicationId &&
                            availableIssues.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    {t('articles.metadata.issue.no_issues')}{' '}
                                    <a
                                        href={edit.url({
                                            publication: selectedPublicationId,
                                        })}
                                        className="underline"
                                    >
                                        {t(
                                            'articles.metadata.issue.manage_issues',
                                        )}
                                    </a>
                                </p>
                            )}
                        <InputError message={errors.publication_issue_id} />
                    </div>
                </div>
            </section>

            <section className="space-y-4">
                <div>
                    <h3 className="text-base font-medium">
                        {t('articles.metadata.categories.heading')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t('articles.metadata.categories.description')}
                    </p>
                </div>

                {!selectedPublicationId || !publicationIssueId ? (
                    <p className="text-sm text-muted-foreground">
                        {t('articles.metadata.categories.select_first')}
                    </p>
                ) : availableCategories.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('articles.metadata.categories.no_categories')}{' '}
                        <a
                            href={edit.url({
                                publication: selectedPublicationId,
                            })}
                            className="underline"
                        >
                            {t('articles.metadata.categories.manage')}
                        </a>
                    </p>
                ) : (
                    <SearchableMultiSelect
                        id="publication-categories"
                        options={categoryOptions}
                        value={publicationCategoryIds}
                        onChange={onPublicationCategoryIdsChange}
                        placeholder={t(
                            'articles.metadata.categories.placeholder',
                        )}
                        searchPlaceholder={t(
                            'articles.metadata.categories.search_placeholder',
                        )}
                        emptyMessage={t('articles.metadata.categories.empty')}
                        removeAriaLabel={(label) =>
                            `${t('common.remove')} ${label}`
                        }
                    />
                )}

                <InputError message={errors.publication_category_ids} />
            </section>

            {canManageEditorSettingsSets && (
                <section className="space-y-4">
                    <div>
                        <h3 className="text-base font-medium">
                            {t('articles.metadata.editor_settings.heading')}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {t('articles.metadata.editor_settings.description')}
                        </p>
                    </div>

                    <div className="grid max-w-lg gap-6">
                        <p className="text-sm text-muted-foreground">
                            {t('articles.metadata.editor_settings.default')}{' '}
                            {defaultEditorSettingsSet ? (
                                <>
                                    {defaultEditorSettingsSet.name} (
                                    {formatEditorSettingsSetSummary(
                                        defaultEditorSettingsSet,
                                        t,
                                    )}
                                    )
                                </>
                            ) : (
                                t(
                                    'articles.metadata.editor_settings.default_fallback',
                                )
                            )}
                        </p>

                        {editorSettingsSets.length > 0 ? (
                            <div className="grid gap-2">
                                <Label htmlFor="editor_settings_set_id">
                                    {t('common.set')}
                                </Label>
                                <Select
                                    value={
                                        editorSettingsSetId
                                            ? String(editorSettingsSetId)
                                            : NONE_VALUE
                                    }
                                    onValueChange={(value) => {
                                        onEditorSettingsSetIdChange(
                                            value === NONE_VALUE
                                                ? null
                                                : Number(value),
                                        );
                                    }}
                                >
                                    <SelectTrigger
                                        id="editor_settings_set_id"
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'articles.metadata.editor_settings.use_default',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent side="top">
                                        <SelectItem value={NONE_VALUE}>
                                            {t(
                                                'articles.metadata.editor_settings.use_default',
                                            )}
                                        </SelectItem>
                                        {editorSettingsSets.map((set) => (
                                            <SelectItem
                                                key={set.id}
                                                value={String(set.id)}
                                            >
                                                {set.name} (
                                                {formatEditorSettingsSetSummary(
                                                    set,
                                                    t,
                                                )}
                                                )
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.editor_settings_set_id}
                                />
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground dark:border-sidebar-border">
                                <p>
                                    {t(
                                        'articles.metadata.editor_settings.create_set_hint',
                                    )}
                                </p>
                                <a
                                    href={createEditorSettingsSet.url()}
                                    className="mt-2 inline-block underline"
                                >
                                    {t('common.create_set')}
                                </a>
                            </div>
                        )}
                    </div>
                </section>
            )}
        </div>
    );
}
