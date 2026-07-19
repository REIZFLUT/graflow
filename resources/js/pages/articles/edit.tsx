import { Head, useForm } from '@inertiajs/react';
import ArticleController from '@/actions/App/Http/Controllers/ArticleController';
import ArticleDocumentEditor from '@/components/articles/article-document-editor';
import ArticleWorkflowActions from '@/components/articles/article-workflow-actions';
import { useArticleMedia } from '@/hooks/use-article-media';
import { translate } from '@/lib/i18n';
import { index } from '@/routes/articles';
import { emptyTipTapDocument } from '@/types';
import type {
    Article,
    ArticleCapabilities,
    ArticleWorkflowAction,
    ArticleWorkflowEvent,
    ArticleWorkflowUser,
    TipTapDocument,
} from '@/types';
import type { PublicationEditorSettings } from '@/types';

type PageProps = {
    article: Article;
    editorSettings: PublicationEditorSettings;
    capabilities: ArticleCapabilities;
    allowedActions: ArticleWorkflowAction[];
    workflowEvents: ArticleWorkflowEvent[];
    authors?: ArticleWorkflowUser[];
    editorialStaff?: ArticleWorkflowUser[];
};

export default function ArticlesEdit({
    article,
    editorSettings,
    capabilities,
    allowedActions,
    workflowEvents,
    authors = [],
    editorialStaff = [],
}: PageProps) {
    const initialContent = article.content ?? emptyTipTapDocument();

    const { mediaItems, uploading, upload, update, remove } = useArticleMedia({
        articleId: article.id,
        initialMedia: article.media ?? [],
    });

    const { data, setData, put, processing, errors, transform } = useForm<{
        title: string;
        content: TipTapDocument;
    }>({
        title: article.title,
        content: initialContent,
    });

    return (
        <>
            <Head title={data.title || article.title} />

            <ArticleDocumentEditor
                title={data.title}
                content={data.content}
                editorSettings={editorSettings}
                onTitleChange={(title) => setData('title', title)}
                onContentChange={(content) => setData('content', content)}
                onSubmit={(content) => {
                    transform((formData) => ({ ...formData, content }));
                    put(ArticleController.update.url({ article: article.id }));
                }}
                processing={processing}
                errors={errors}
                status={article.status}
                readOnly={!capabilities.update_content}
                canManageMetadata={capabilities.manage_workflow}
                workflowActions={
                    <ArticleWorkflowActions
                        articleId={article.id}
                        capabilities={capabilities}
                        allowedActions={allowedActions}
                        authors={authors}
                        editorialStaff={editorialStaff}
                    />
                }
                articleId={article.id}
                currentAssignee={article.current_assignee}
                submissionDeadline={article.submission_deadline}
                targetCharacterCount={article.target_character_count}
                versions={article.versions ?? []}
                workflowEvents={workflowEvents}
                mediaItems={mediaItems}
                mediaUploading={uploading}
                onMediaUpload={capabilities.update_content ? upload : undefined}
                onMediaUpdate={capabilities.update_content ? update : undefined}
                onMediaDelete={
                    capabilities.update_content
                        ? (media) => remove(media.id)
                        : undefined
                }
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
