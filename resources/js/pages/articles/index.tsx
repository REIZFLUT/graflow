import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, translate } from '@/lib/i18n';
import type { TranslateFn } from '@/lib/i18n';
import { edit, index } from '@/routes/articles';
import { formatPublicationAssignment } from '@/types';
import type { Article, PaginatedArticles } from '@/types';

type PageProps = {
    articles: PaginatedArticles;
};

export default function ArticlesIndex({ articles }: PageProps) {
    const { t, locale } = useTranslation();

    return (
        <>
            <Head title={t('articles.title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={t('articles.title')}
                    description={t('articles.description')}
                />

                {articles.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('articles.empty')}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full min-w-6xl text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.title')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.status')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.publication')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.chapter_position')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.author')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.assignee')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.deadline')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('articles.table.target_count')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        {t('common.action')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {articles.data.map((article) => (
                                    <tr
                                        key={article.id}
                                        className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {article.title}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline">
                                                {t(
                                                    `articles.status.${article.status}`,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatPublicationAssignment(
                                                article.publication_issue,
                                                t,
                                            ) ?? t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatChapterAssignment(
                                                article,
                                                t,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {article.author?.name ??
                                                t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {article.current_assignee?.name ??
                                                t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {article.submission_deadline
                                                ? formatDateTime(
                                                      article.submission_deadline,
                                                      locale,
                                                      {
                                                          dateStyle: 'medium',
                                                      },
                                                  )
                                                : t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {article.target_character_count?.toLocaleString(
                                                locale,
                                            ) ?? t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={edit({
                                                        article: article.id,
                                                    })}
                                                    prefetch
                                                >
                                                    {t('common.edit')}
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {articles.last_page > 1 && (
                    <nav
                        className="flex flex-wrap justify-center gap-2"
                        aria-label={t('articles.pagination')}
                    >
                        {articles.links.map((link, linkIndex) =>
                            link.url ? (
                                <Button
                                    key={`${link.label}-${linkIndex}`}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                    >
                                        {formatPaginationLabel(link.label)}
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    key={`${link.label}-${linkIndex}`}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                >
                                    {formatPaginationLabel(link.label)}
                                </Button>
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

function formatPaginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}

function formatChapterAssignment(article: Article, t: TranslateFn): string {
    const chapter =
        article.publication_chapter ??
        article.publication_issue?.chapters?.find(
            (item) => item.id === article.publication_chapter_id,
        );

    if (chapter) {
        return t('articles.assignment.with_chapter', {
            chapter: `${chapter.position}. ${chapter.title}`,
            position: article.position,
        });
    }

    if (article.publication_chapter_id) {
        return t('articles.assignment.with_chapter_id', {
            chapter: article.publication_chapter_id,
            position: article.position,
        });
    }

    return t('articles.assignment.unassigned_position', {
        position: article.position,
    });
}

ArticlesIndex.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.articles'),
            href: index(),
        },
    ],
});
