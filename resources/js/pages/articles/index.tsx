import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/i18n';
import { getArticleStatusLabel } from '@/lib/article-status';
import { create, edit, index } from '@/routes/articles';
import { formatPublicationAssignment, type PaginatedArticles } from '@/types';
import { translate } from '@/lib/i18n';

type PageProps = {
    articles: PaginatedArticles;
};

export default function ArticlesIndex({ articles }: PageProps) {
    const { t, locale } = useTranslation();

    return (
        <>
            <Head title={t('articles.title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-6">
                    <Heading
                        title={t('articles.title')}
                        description={t('articles.description')}
                    />

                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus className="size-4" />
                            {t('articles.new_article')}
                        </Link>
                    </Button>
                </div>

                {articles.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('articles.empty')}
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create()} prefetch>
                                {t('articles.create_first')}
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
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
                                        {t('articles.table.updated_at')}
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
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {getArticleStatusLabel(
                                                article.status,
                                                t,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatPublicationAssignment(
                                                article.publication_issue,
                                                t,
                                            ) ?? t('common.em_dash')}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatDateTime(
                                                article.updated_at,
                                                locale,
                                            )}
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
            </div>
        </>
    );
}

ArticlesIndex.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.articles'),
            href: index(),
        },
    ],
});
