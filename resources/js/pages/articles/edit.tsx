import { Head, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import ArticleController from '@/actions/App/Http/Controllers/ArticleController';
import ArticleDocumentEditor from '@/components/articles/article-document-editor';
import { useArticleMedia } from '@/hooks/use-article-media';
import { index } from '@/routes/articles';
import { translate } from '@/lib/i18n';
import { emptyTipTapDocument, type Article, type TipTapDocument } from '@/types';
import type { PublicationEditorSettings } from '@/types';

type PageProps = {
    article: Article;
    editorSettings: PublicationEditorSettings;
};

export default function ArticlesEdit({ article, editorSettings }: PageProps) {
    const initialContent = article.content ?? emptyTipTapDocument();
    const contentRef = useRef<TipTapDocument>(initialContent);

    const {
        mediaItems,
        uploading,
        upload,
        update,
        remove,
    } = useArticleMedia({
        articleId: article.id,
        initialMedia: article.media ?? [],
    });

    const { data, setData, put, processing, errors, transform } = useForm<{
        title: string;
        content: TipTapDocument;
        status: string;
    }>({
        title: article.title,
        content: initialContent,
        status: article.status,
    });

    transform((formData) => ({
        ...formData,
        content: contentRef.current,
    }));

    return (
        <>
            <Head title={data.title || article.title} />

            <ArticleDocumentEditor
                title={data.title}
                content={data.content}
                editorSettings={editorSettings}
                onTitleChange={(title) => setData('title', title)}
                onContentChange={(content) => {
                    contentRef.current = content;
                    setData('content', content);
                }}
                onSubmit={() =>
                    put(ArticleController.update.url({ article: article.id }))
                }
                processing={processing}
                errors={errors}
                status={data.status}
                onStatusChange={(status) => setData('status', status)}
                articleId={article.id}
                versions={article.versions ?? []}
                mediaItems={mediaItems}
                mediaUploading={uploading}
                onMediaUpload={upload}
                onMediaUpdate={update}
                onMediaDelete={(media) => remove(media.id)}
            />
        </>
    );
}

ArticlesEdit.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.articles'),
            href: index(),
        },
        {
            title: translate(props.translations, 'articles.edit.breadcrumb'),
            href: index(),
        },
    ],
});
