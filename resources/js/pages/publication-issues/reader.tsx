import { Head } from '@inertiajs/react';
import IssueArticleReader from '@/components/publications/issue-article-reader';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { getArticleStatusLabel } from '@/lib/article-status';
import type {
    ArticleMedia,
    ArticleStatus,
    PublicationEditorSettings,
    TipTapDocument,
} from '@/types';

type ReaderChapter = {
    id: number;
    title: string;
    position: number;
};

type ReaderArticle = {
    id: number;
    title: string;
    status: ArticleStatus;
    author: { id: number; name: string } | null;
    publication_chapter_id: number | null;
    position: number;
    content: TipTapDocument | null;
    media: ArticleMedia[];
    editor_settings: PublicationEditorSettings;
};

type PageProps = {
    publication: {
        id: number;
        name: string;
    };
    issue: {
        id: number;
        label: string;
        publication_id: number;
    };
    chapters: ReaderChapter[];
    articles: ReaderArticle[];
};

export default function PublicationIssueReader({
    publication,
    issue,
    chapters,
    articles,
}: PageProps) {
    const { t } = useTranslation();
    const pageTitle = t('publications.reader.title', {
        publication: publication.name,
        issue: issue.label,
    });

    const articlesForChapter = (chapterId: number | null): ReaderArticle[] =>
        articles.filter((article) => article.publication_chapter_id === chapterId);

    const unassignedArticles = articlesForChapter(null);
    const hasAnyArticles = articles.length > 0;

    return (
        <>
            <Head title={pageTitle} />

            <header className="sticky top-0 z-10 border-b border-border/60 bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/80">
                <div className="mx-auto flex max-w-5xl flex-col gap-1 px-6 py-4 md:px-4">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        {t('publications.reader.read_only_notice')}
                    </p>
                    <h1 className="text-lg font-semibold tracking-tight md:text-xl">
                        {publication.name}
                        <span className="mx-2 text-muted-foreground">·</span>
                        <span className="font-medium text-muted-foreground">
                            {issue.label}
                        </span>
                    </h1>
                </div>
            </header>

            <main className="mx-auto flex max-w-5xl flex-col gap-12 px-6 py-10 md:px-4 md:py-14">
                {!hasAnyArticles ? (
                    <p className="text-center text-sm text-muted-foreground">
                        {t('publications.reader.empty')}
                    </p>
                ) : (
                    <>
                        {chapters.map((chapter) => {
                            const chapterArticles = articlesForChapter(
                                chapter.id,
                            );

                            if (chapterArticles.length === 0) {
                                return null;
                            }

                            return (
                                <section
                                    key={chapter.id}
                                    className="space-y-8"
                                >
                                    <h2 className="border-b border-border/60 pb-3 text-2xl font-semibold tracking-tight">
                                        {chapter.position}. {chapter.title}
                                    </h2>
                                    {chapterArticles.map((article) => (
                                        <ReaderArticleSection
                                            key={article.id}
                                            article={article}
                                        />
                                    ))}
                                </section>
                            );
                        })}

                        {unassignedArticles.length > 0 && (
                            <section className="space-y-8">
                                <h2 className="border-b border-border/60 pb-3 text-2xl font-semibold tracking-tight">
                                    {t('publications.reader.unassigned')}
                                </h2>
                                {unassignedArticles.map((article) => (
                                    <ReaderArticleSection
                                        key={article.id}
                                        article={article}
                                    />
                                ))}
                            </section>
                        )}
                    </>
                )}
            </main>
        </>
    );
}

function ReaderArticleSection({ article }: { article: ReaderArticle }) {
    const { t } = useTranslation();

    return (
        <section className="space-y-4">
            <div className="flex flex-wrap items-center gap-3 px-1">
                <Badge variant="secondary">
                    {getArticleStatusLabel(article.status, t)}
                </Badge>
                {article.author && (
                    <span className="text-sm text-muted-foreground">
                        {t('publications.reader.by_author', {
                            name: article.author.name,
                        })}
                    </span>
                )}
            </div>

            <IssueArticleReader
                title={article.title}
                content={article.content}
                editorSettings={article.editor_settings}
                mediaItems={article.media}
            />
        </section>
    );
}
