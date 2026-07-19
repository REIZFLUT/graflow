import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import ArticleMetadataController from '@/actions/App/Http/Controllers/ArticleMetadataController';
import ArticleMetadataForm from '@/components/articles/article-metadata-form';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { edit as editArticle, index } from '@/routes/articles';
import type { Article, EditorSettingsSet, Publication } from '@/types';

type PageProps = {
    article: Article;
    publications: Publication[];
    editorSettingsSets: EditorSettingsSet[];
    defaultEditorSettingsSet: EditorSettingsSet | null;
    canEdit: boolean;
};

export default function ArticlesMetadata({
    article,
    publications,
    editorSettingsSets,
    defaultEditorSettingsSet,
    canEdit,
}: PageProps) {
    const { t } = useTranslation();

    const { data, setData, patch, processing, errors } = useForm<{
        publication_issue_id: number | null;
        publication_chapter_id: number | null;
        position: number;
        publication_category_ids: number[];
        editor_settings_set_id: number | null;
    }>({
        publication_issue_id: article.publication_issue_id,
        publication_chapter_id: article.publication_chapter_id,
        position: article.position,
        publication_category_ids:
            article.publication_categories?.map((category) => category.id) ??
            [],
        editor_settings_set_id: article.editor_settings_set_id,
    });

    return (
        <>
            <Head
                title={t('articles.metadata.head_title', {
                    title: article.title,
                })}
            />

            <div className="flex flex-col gap-8 p-4 pb-32 md:p-6 md:pb-32 lg:p-8 lg:pb-32">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={editArticle({ article: article.id })}
                                prefetch
                            >
                                <ArrowLeft className="size-4" />
                                {t('articles.metadata.back_to_editor')}
                            </Link>
                        </Button>
                    </div>

                    {canEdit && (
                        <Button
                            type="submit"
                            form="article-metadata-form"
                            size="sm"
                            disabled={processing}
                        >
                            {processing ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Save className="size-4" />
                            )}
                            {t('common.save')}
                        </Button>
                    )}
                </div>

                <Heading
                    title={t('articles.metadata.title')}
                    description={article.title}
                />

                <form
                    id="article-metadata-form"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (!canEdit) {
                            return;
                        }

                        patch(
                            ArticleMetadataController.update.url({
                                article: article.id,
                            }),
                        );
                    }}
                >
                    <ArticleMetadataForm
                        author={article.author}
                        publications={publications}
                        assignedPublicationId={
                            article.publication_issue?.publication_id ?? null
                        }
                        publicationIssueId={data.publication_issue_id}
                        onPublicationIssueIdChange={(issueId) =>
                            setData('publication_issue_id', issueId)
                        }
                        publicationChapterId={data.publication_chapter_id}
                        onPublicationChapterIdChange={(chapterId) =>
                            setData('publication_chapter_id', chapterId)
                        }
                        position={data.position}
                        onPositionChange={(position) =>
                            setData('position', position)
                        }
                        publicationCategoryIds={data.publication_category_ids}
                        onPublicationCategoryIdsChange={(categoryIds) =>
                            setData('publication_category_ids', categoryIds)
                        }
                        editorSettingsSets={editorSettingsSets}
                        editorSettingsSetId={data.editor_settings_set_id}
                        onEditorSettingsSetIdChange={(setId) =>
                            setData('editor_settings_set_id', setId)
                        }
                        defaultEditorSettingsSet={defaultEditorSettingsSet}
                        errors={errors}
                        readOnly={!canEdit}
                    />
                </form>
            </div>
        </>
    );
}

ArticlesMetadata.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.articles'),
            href: index(),
        },
        {
            title: translate(
                props.translations,
                'articles.metadata.breadcrumb',
            ),
            href: index(),
        },
    ],
});
