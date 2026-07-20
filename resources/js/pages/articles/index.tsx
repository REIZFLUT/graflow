import { Head, router } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable
    
    
    
} from '@tanstack/react-table';
import type {OnChangeFn, SortingState, VisibilityState} from '@tanstack/react-table';
import { Archive, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableViewOptions } from '@/components/data-table/data-table-view-options';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { index } from '@/routes/articles';
import type {
    ArticleFilterOptions,
    ArticleFilters,
    PaginatedArticles,
} from '@/types';
import { buildArticleColumns } from './columns';

const STORAGE_KEY = 'articles.datatable.v1';
const DEFAULT_PER_PAGE = 15;
const ALL_VALUE = '__all__';

type PageProps = {
    articles: PaginatedArticles;
    filters: ArticleFilters;
    filterOptions: ArticleFilterOptions;
};

type StoredState = {
    columnVisibility?: VisibilityState;
    filters?: Partial<ArticleFilters>;
};

type QueryParams = Record<string, string | number>;

function loadStoredState(): StoredState | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        return raw ? (JSON.parse(raw) as StoredState) : null;
    } catch {
        return null;
    }
}

function buildParamsFromFilters(filters: Partial<ArticleFilters>): QueryParams {
    const params: QueryParams = {};

    if (filters.search) {
        params.search = filters.search;
    }

    if (filters.sort) {
        params.sort = filters.sort;

        if (filters.direction) {
            params.direction = filters.direction;
        }
    }

    if (filters.publication_id) {
        params.publication_id = filters.publication_id;
    }

    if (filters.issue_id) {
        params.issue_id = filters.issue_id;
    }

    if (filters.author_id) {
        params.author_id = filters.author_id;
    }

    if (filters.archived) {
        params.archived = 1;
    }

    if (filters.per_page && filters.per_page !== DEFAULT_PER_PAGE) {
        params.per_page = filters.per_page;
    }

    return params;
}

export default function ArticlesIndex({
    articles,
    filters,
    filterOptions,
}: PageProps) {
    const { t, locale } = useTranslation();

    const [searchValue, setSearchValue] = useState(filters.search ?? '');
    const [sorting, setSorting] = useState<SortingState>(
        filters.sort
            ? [{ id: filters.sort, desc: filters.direction === 'desc' }]
            : [],
    );
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        () => loadStoredState()?.columnVisibility ?? {},
    );
    const [loading, setLoading] = useState(false);

    const columns = buildArticleColumns(t, locale);

    function navigate(params: QueryParams): void {
        router.get(index.url(), params, {
            only: ['articles', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function applyChange(partial: Partial<ArticleFilters> & { page?: number }) {
        const current: Partial<ArticleFilters> & { page?: number } = {
            search: searchValue.trim() || null,
            sort: (sorting[0]?.id as ArticleFilters['sort']) ?? null,
            direction: sorting[0]
                ? sorting[0].desc
                    ? 'desc'
                    : 'asc'
                : null,
            publication_id: filters.publication_id,
            issue_id: filters.issue_id,
            author_id: filters.author_id,
            archived: filters.archived,
            per_page: filters.per_page,
            page: articles.current_page,
            ...partial,
        };

        const params = buildParamsFromFilters(current);

        if (current.page && current.page > 1) {
            params.page = current.page;
        }

        navigate(params);
    }

    const handleSortingChange: OnChangeFn<SortingState> = (updater) => {
        const next =
            typeof updater === 'function' ? updater(sorting) : updater;
        setSorting(next);

        const first = next[0];
        applyChange({
            sort: (first?.id as ArticleFilters['sort']) ?? null,
            direction: first ? (first.desc ? 'desc' : 'asc') : null,
            page: 1,
        });
    };

    const table = useReactTable({
        data: articles.data,
        columns,
        state: { sorting, columnVisibility },
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        pageCount: articles.last_page,
        onSortingChange: handleSortingChange,
        onColumnVisibilityChange: setColumnVisibility,
        getCoreRowModel: getCoreRowModel(),
    });

    useEffect(() => {
        const stopStart = router.on('start', () => setLoading(true));
        const stopFinish = router.on('finish', () => setLoading(false));

        return () => {
            stopStart();
            stopFinish();
        };
    }, []);

    const didInit = useRef(false);
    useEffect(() => {
        if (didInit.current || typeof window === 'undefined') {
            return;
        }

        didInit.current = true;

        const hasQuery = window.location.search.replace('?', '').length > 0;
        const stored = loadStoredState();
        const storedFilters = stored?.filters;

        if (hasQuery || !storedFilters) {
            return;
        }

        const hasSavedFilters =
            Boolean(storedFilters.search) ||
            Boolean(storedFilters.sort) ||
            Boolean(storedFilters.publication_id) ||
            Boolean(storedFilters.issue_id) ||
            Boolean(storedFilters.author_id) ||
            Boolean(storedFilters.archived) ||
            (Boolean(storedFilters.per_page) &&
                storedFilters.per_page !== DEFAULT_PER_PAGE);

        if (!hasSavedFilters) {
            return;
        }

        setSearchValue(storedFilters.search ?? '');
        setSorting(
            storedFilters.sort
                ? [
                      {
                          id: storedFilters.sort,
                          desc: storedFilters.direction === 'desc',
                      },
                  ]
                : [],
        );
        navigate(buildParamsFromFilters(storedFilters));
         
    }, []);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        try {
            window.localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ columnVisibility, filters }),
            );
        } catch {
            // Ignore write failures (e.g. storage disabled).
        }
    }, [columnVisibility, filters]);

    useEffect(() => {
        const handle = window.setTimeout(() => {
            const nextSearch = searchValue.trim() || null;

            if (nextSearch !== (filters.search ?? null)) {
                applyChange({ search: nextSearch, page: 1 });
            }
        }, 300);

        return () => window.clearTimeout(handle);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchValue]);

    const availableIssues = filters.publication_id
        ? filterOptions.issues.filter(
              (issue) => issue.publication_id === filters.publication_id,
          )
        : filterOptions.issues;

    const hasActiveFilters =
        Boolean(filters.search) ||
        Boolean(filters.sort) ||
        filters.publication_id !== null ||
        filters.issue_id !== null ||
        filters.author_id !== null ||
        filters.per_page !== DEFAULT_PER_PAGE;

    function resetFilters(): void {
        setSearchValue('');
        setSorting([]);
        navigate(filters.archived ? { archived: 1 } : {});
    }

    const columnCount = table.getVisibleFlatColumns().length;
    const showEmptyState =
        articles.data.length === 0 && !hasActiveFilters && !filters.archived;

    return (
        <>
            <Head title={t('articles.title')} />

            <div className="flex min-w-0 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <Heading
                    title={t('articles.title')}
                    description={t('articles.description')}
                />

                {showEmptyState ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('articles.empty')}
                        </p>
                    </div>
                ) : (
                    <div className="flex min-w-0 flex-col gap-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Input
                                type="search"
                                value={searchValue}
                                onChange={(event) =>
                                    setSearchValue(event.target.value)
                                }
                                placeholder={t(
                                    'articles.filters.search_placeholder',
                                )}
                                className="h-8 w-full sm:max-w-xs"
                            />

                            <Select
                                value={
                                    filters.publication_id
                                        ? String(filters.publication_id)
                                        : ALL_VALUE
                                }
                                onValueChange={(value) =>
                                    applyChange({
                                        publication_id:
                                            value === ALL_VALUE
                                                ? null
                                                : Number(value),
                                        issue_id: null,
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger size="sm" className="w-auto">
                                    <SelectValue
                                        placeholder={t(
                                            'articles.filters.publication',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('articles.filters.publication')}:{' '}
                                        {t('articles.filters.all')}
                                    </SelectItem>
                                    {filterOptions.publications.map(
                                        (publication) => (
                                            <SelectItem
                                                key={publication.id}
                                                value={String(publication.id)}
                                            >
                                                {publication.name}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>

                            <Select
                                value={
                                    filters.issue_id
                                        ? String(filters.issue_id)
                                        : ALL_VALUE
                                }
                                onValueChange={(value) =>
                                    applyChange({
                                        issue_id:
                                            value === ALL_VALUE
                                                ? null
                                                : Number(value),
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger size="sm" className="w-auto">
                                    <SelectValue
                                        placeholder={t(
                                            'articles.filters.issue',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('articles.filters.issue')}:{' '}
                                        {t('articles.filters.all')}
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

                            <Select
                                value={
                                    filters.author_id
                                        ? String(filters.author_id)
                                        : ALL_VALUE
                                }
                                onValueChange={(value) =>
                                    applyChange({
                                        author_id:
                                            value === ALL_VALUE
                                                ? null
                                                : Number(value),
                                        page: 1,
                                    })
                                }
                            >
                                <SelectTrigger size="sm" className="w-auto">
                                    <SelectValue
                                        placeholder={t(
                                            'articles.filters.author',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_VALUE}>
                                        {t('articles.filters.author')}:{' '}
                                        {t('articles.filters.all')}
                                    </SelectItem>
                                    {filterOptions.authors.map((author) => (
                                        <SelectItem
                                            key={author.id}
                                            value={String(author.id)}
                                        >
                                            {author.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {hasActiveFilters && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={resetFilters}
                                >
                                    <X className="size-4" />
                                    {t('articles.filters.reset')}
                                </Button>
                            )}

                            <Button
                                variant={
                                    filters.archived ? 'default' : 'outline'
                                }
                                size="sm"
                                onClick={() =>
                                    applyChange({
                                        archived: !filters.archived,
                                        page: 1,
                                    })
                                }
                            >
                                <Archive className="size-4" />
                                {filters.archived
                                    ? t('articles.filters.active')
                                    : t('articles.filters.archive')}
                            </Button>

                            <DataTableViewOptions
                                table={table}
                                columnVisibility={columnVisibility}
                            />
                        </div>

                        <div className="relative min-w-0 overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table
                                className={
                                    loading
                                        ? 'opacity-50 transition-opacity'
                                        : 'transition-opacity'
                                }
                            >
                                <TableHeader>
                                    {table
                                        .getHeaderGroups()
                                        .map((headerGroup) => (
                                            <TableRow key={headerGroup.id}>
                                                {headerGroup.headers.map(
                                                    (header) => (
                                                        <TableHead
                                                            key={header.id}
                                                        >
                                                            {header.isPlaceholder
                                                                ? null
                                                                : flexRender(
                                                                      header
                                                                          .column
                                                                          .columnDef
                                                                          .header,
                                                                      header.getContext(),
                                                                  )}
                                                        </TableHead>
                                                    ),
                                                )}
                                            </TableRow>
                                        ))}
                                </TableHeader>
                                <TableBody>
                                    {table.getRowModel().rows.length > 0 ? (
                                        table.getRowModel().rows.map((row) => (
                                            <TableRow key={row.id}>
                                                {row
                                                    .getVisibleCells()
                                                    .map((cell) => (
                                                        <TableCell
                                                            key={cell.id}
                                                        >
                                                            {flexRender(
                                                                cell.column
                                                                    .columnDef
                                                                    .cell,
                                                                cell.getContext(),
                                                            )}
                                                        </TableCell>
                                                    ))}
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={columnCount}
                                                className="h-24 text-center text-muted-foreground"
                                            >
                                                {filters.archived &&
                                                !hasActiveFilters
                                                    ? t(
                                                          'articles.filters.empty_archive',
                                                      )
                                                    : t(
                                                          'articles.table.no_results',
                                                      )}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>

                            {loading && (
                                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                    <Spinner className="size-6 text-muted-foreground" />
                                </div>
                            )}
                        </div>

                        <DataTablePagination
                            currentPage={articles.current_page}
                            lastPage={articles.last_page}
                            perPage={articles.per_page}
                            onPageChange={(page) => applyChange({ page })}
                            onPerPageChange={(perPage) =>
                                applyChange({ per_page: perPage, page: 1 })
                            }
                        />
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
