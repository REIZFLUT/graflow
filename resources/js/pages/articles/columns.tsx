import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import '@/components/data-table/column-meta';
import { DataTableColumnHeader } from '@/components/data-table/data-table-column-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getArticleStatusLabel } from '@/lib/article-status';
import { formatDateTime  } from '@/lib/i18n';
import type {TranslateFn} from '@/lib/i18n';
import { edit } from '@/routes/articles';
import { formatPublicationAssignment } from '@/types';
import type { Article } from '@/types';

function formatCompactChapterAssignment(article: Article): string {
    const chapter =
        article.publication_chapter ??
        article.publication_issue?.chapters?.find(
            (item) => item.id === article.publication_chapter_id,
        );

    if (chapter) {
        return `Kap. ${chapter.position} · Pos. ${article.position}`;
    }

    if (article.publication_chapter_id) {
        return `Kap. ${article.publication_chapter_id} · Pos. ${article.position}`;
    }

    return `Pos. ${article.position}`;
}

export function buildArticleColumns(
    t: TranslateFn,
    locale: string,
): ColumnDef<Article>[] {
    const emDash = t('common.em_dash');

    return [
        {
            id: 'title',
            accessorKey: 'title',
            enableHiding: true,
            enableSorting: true,
            meta: { label: t('articles.table.title') },
            header: ({ column }) => (
                <DataTableColumnHeader
                    column={column}
                    title={t('articles.table.title')}
                />
            ),
            cell: ({ row }) => (
                <span
                    className="block max-w-[24rem] truncate font-medium"
                    title={row.original.title}
                >
                    {row.original.title}
                </span>
            ),
        },
        {
            id: 'status',
            accessorKey: 'status',
            enableHiding: true,
            enableSorting: true,
            meta: { label: t('articles.table.status') },
            header: ({ column }) => (
                <DataTableColumnHeader
                    column={column}
                    title={t('articles.table.status')}
                />
            ),
            cell: ({ row }) => {
                const label = getArticleStatusLabel(row.original.status, t);

                return (
                    <Badge
                        variant="outline"
                        className="max-w-full truncate"
                        title={label}
                    >
                        {label}
                    </Badge>
                );
            },
        },
        {
            id: 'publication',
            accessorFn: (article) =>
                formatPublicationAssignment(article.publication_issue, t) ?? '',
            enableHiding: true,
            enableSorting: true,
            meta: { label: t('articles.table.publication') },
            header: ({ column }) => (
                <DataTableColumnHeader
                    column={column}
                    title={t('articles.table.publication')}
                />
            ),
            cell: ({ row }) => {
                const publicationLabel =
                    formatPublicationAssignment(
                        row.original.publication_issue,
                        t,
                    ) ?? emDash;
                const chapterLabel = formatCompactChapterAssignment(
                    row.original,
                );

                return (
                    <div className="max-w-[16rem]">
                        <span
                            className="block truncate text-muted-foreground"
                            title={publicationLabel}
                        >
                            {publicationLabel}
                        </span>
                        <span
                            className="mt-0.5 block truncate text-xs text-muted-foreground"
                            title={chapterLabel}
                        >
                            {chapterLabel}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'author',
            accessorFn: (article) => article.author?.name ?? '',
            enableHiding: true,
            enableSorting: false,
            meta: { label: t('articles.table.author') },
            header: () => <span>{t('articles.table.author')}</span>,
            cell: ({ row }) => {
                const name = row.original.author?.name ?? emDash;

                return (
                    <span
                        className="block max-w-[12rem] truncate text-muted-foreground"
                        title={row.original.author?.name ?? undefined}
                    >
                        {name}
                    </span>
                );
            },
        },
        {
            id: 'assignee',
            accessorFn: (article) => article.current_assignee?.name ?? '',
            enableHiding: true,
            enableSorting: true,
            meta: { label: t('articles.table.assignee') },
            header: ({ column }) => (
                <DataTableColumnHeader
                    column={column}
                    title={t('articles.table.assignee')}
                />
            ),
            cell: ({ row }) => {
                const name = row.original.current_assignee?.name ?? emDash;

                return (
                    <span
                        className="block max-w-[12rem] truncate text-muted-foreground"
                        title={row.original.current_assignee?.name ?? undefined}
                    >
                        {name}
                    </span>
                );
            },
        },
        {
            id: 'deadline',
            accessorFn: (article) => article.submission_deadline ?? '',
            enableHiding: true,
            enableSorting: true,
            meta: { label: t('articles.table.deadline') },
            header: ({ column }) => (
                <DataTableColumnHeader
                    column={column}
                    title={t('articles.table.deadline')}
                />
            ),
            cell: ({ row }) =>
                row.original.submission_deadline ? (
                    <span className="text-muted-foreground">
                        {formatDateTime(
                            row.original.submission_deadline,
                            locale,
                            { dateStyle: 'medium' },
                        )}
                    </span>
                ) : (
                    <span className="text-muted-foreground">{emDash}</span>
                ),
        },
        {
            id: 'target_count',
            accessorFn: (article) => article.target_character_count ?? 0,
            enableHiding: true,
            enableSorting: false,
            meta: { label: t('articles.table.target_count') },
            header: () => <span>{t('articles.table.target_count')}</span>,
            cell: ({ row }) => (
                <span className="text-muted-foreground tabular-nums">
                    {row.original.target_character_count ?? emDash}
                </span>
            ),
        },
        {
            id: 'actions',
            enableHiding: false,
            enableSorting: false,
            meta: { label: t('articles.table.actions') },
            header: () => (
                <span className="sr-only">{t('articles.table.actions')}</span>
            ),
            cell: ({ row }) => (
                <div className="text-right">
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={edit({ article: row.original.id })}
                            prefetch
                        >
                            {t('common.edit')}
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];
}
