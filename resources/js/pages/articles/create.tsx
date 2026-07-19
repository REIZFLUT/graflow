import { Head, useForm } from '@inertiajs/react';
import ArticleController from '@/actions/App/Http/Controllers/ArticleController';
import ArticleDocumentEditor from '@/components/articles/article-document-editor';
import { clearStagingToken, useArticleMedia } from '@/hooks/use-article-media';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { create, index } from '@/routes/articles';
import { defaultPublicationEditorSettings, emptyTipTapDocument } from '@/types';
import type { TipTapDocument } from '@/types';

type PageProps = {
    editorSettings?: typeof defaultPublicationEditorSettings;
};

export default function ArticlesCreate({
    editorSettings = defaultPublicationEditorSettings,
}: PageProps) {
    const { t } = useTranslation();
    const initialContent = emptyTipTapDocument();

    const { mediaItems, stagingToken, uploading, upload, update, remove } =
        useArticleMedia({});

    const { data, setData, post, processing, errors, transform } = useForm<{
        title: string;
        content: TipTapDocument;
        staging_token?: string | null;
    }>({
        title: '',
        content: initialContent,
        staging_token: stagingToken,
    });

    return (
        <>
            <Head title={t('articles.create.head_title')} />

            <ArticleDocumentEditor
                title={data.title}
                content={data.content}
                editorSettings={editorSettings}
                onTitleChange={(title) => setData('title', title)}
                onContentChange={(content) => setData('content', content)}
                onSubmit={(content) => {
                    transform((formData) => ({
                        ...formData,
                        content,
                        staging_token: stagingToken,
                    }));
                    post(ArticleController.store.url(), {
                        onSuccess: () => {
                            clearStagingToken();
                        },
                    });
                }}
                processing={processing}
                errors={errors}
                mediaItems={mediaItems}
                mediaUploading={uploading}
                onMediaUpload={upload}
                onMediaUpdate={update}
                onMediaDelete={(media) => remove(media.id)}
            />
        </>
    );
}

ArticlesCreate.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.articles'),
            href: index(),
        },
        {
            title: translate(props.translations, 'common.new'),
            href: create(),
        },
    ],
});
