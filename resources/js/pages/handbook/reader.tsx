import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { storeArticle } from '@/actions/App/Http/Controllers/HandbookController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import IssueArticleReader from '@/components/publications/issue-article-reader';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { tiptapToPlainText } from '@/lib/tiptap-text';
import {
    destroy as destroyArticle,
    edit as editArticle,
} from '@/routes/articles';
import { show as handbookShow } from '@/routes/handbook';
import type {
    ArticleMedia,
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
    publication_chapter_id: number | null;
    position: number;
    content: TipTapDocument | null;
    media: ArticleMedia[];
    editor_settings: PublicationEditorSettings;
};

type PageProps = {
    title: string;
    chapters: ReaderChapter[];
    articles: ReaderArticle[];
    canManage: boolean;
};

const articleAnchorId = (id: number): string => `handbook-article-${id}`;

export default function HandbookReader({
    title,
    chapters,
    articles,
    canManage,
}: PageProps) {
    const { t } = useTranslation();
    const [query, setQuery] = useState('');
    const [collapsedChapters, setCollapsedChapters] = useState<Set<string>>(
        new Set(),
    );

    const toggleChapter = useCallback((key: string) => {
        setCollapsedChapters((current) => {
            const next = new Set(current);

            if (next.has(key)) {
                next.delete(key);
            } else {
                next.add(key);
            }

            return next;
        });
    }, []);

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('nav.handbook'),
                href: handbookShow(),
            },
        ],
    });

    const sortedChapters = useMemo(
        () =>
            [...chapters].sort(
                (first, second) => first.position - second.position,
            ),
        [chapters],
    );

    const searchIndex = useMemo(() => {
        const index = new Map<number, string>();

        for (const article of articles) {
            index.set(
                article.id,
                `${article.title}\n${tiptapToPlainText(article.content)}`.toLowerCase(),
            );
        }

        return index;
    }, [articles]);

    const normalizedQuery = query.trim().toLowerCase();
    const isSearching = normalizedQuery.length > 0;

    const matchedIds = useMemo(() => {
        if (!isSearching) {
            return null;
        }

        const matches = new Set<number>();

        for (const [id, text] of searchIndex) {
            if (text.includes(normalizedQuery)) {
                matches.add(id);
            }
        }

        return matches;
    }, [searchIndex, normalizedQuery, isSearching]);

    const visibleArticles = useMemo(
        () =>
            matchedIds === null
                ? articles
                : articles.filter((article) => matchedIds.has(article.id)),
        [articles, matchedIds],
    );

    const articlesForChapter = (chapterId: number | null): ReaderArticle[] =>
        visibleArticles.filter(
            (article) => article.publication_chapter_id === chapterId,
        );

    const unassignedArticles = articlesForChapter(null);
    const hasAnyArticles = articles.length > 0;
    const hasVisibleArticles = visibleArticles.length > 0;

    const tocGroupKeys = [
        ...sortedChapters
            .filter((chapter) => articlesForChapter(chapter.id).length > 0)
            .map((chapter) => String(chapter.id)),
        ...(unassignedArticles.length > 0 ? ['unassigned'] : []),
    ];
    const allChaptersCollapsed =
        tocGroupKeys.length > 0 &&
        tocGroupKeys.every((key) => collapsedChapters.has(key));

    const scrollToArticle = (id: number) => {
        document
            .getElementById(articleAnchorId(id))
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const renderTocGroup = (
        heading: string,
        groupArticles: ReaderArticle[],
        key: string | number,
    ) => {
        if (groupArticles.length === 0) {
            return null;
        }

        const groupKey = String(key);
        const isExpanded = isSearching || !collapsedChapters.has(groupKey);

        return (
            <div key={groupKey} className="space-y-1">
                <button
                    type="button"
                    onClick={() => toggleChapter(groupKey)}
                    aria-expanded={isExpanded}
                    disabled={isSearching}
                    className="flex w-full items-center gap-1 rounded-md px-2 py-1 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase transition-colors hover:bg-muted hover:text-foreground disabled:cursor-default disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
                    title={
                        isExpanded
                            ? t('handbook.toc.collapse')
                            : t('handbook.toc.expand')
                    }
                >
                    {isExpanded ? (
                        <ChevronDown className="size-3.5 shrink-0" />
                    ) : (
                        <ChevronRight className="size-3.5 shrink-0" />
                    )}
                    <span className="truncate">{heading}</span>
                </button>
                {isExpanded && (
                    <ul className="space-y-0.5 pl-4">
                        {groupArticles.map((article) => (
                            <li key={article.id}>
                                <button
                                    type="button"
                                    onClick={() => scrollToArticle(article.id)}
                                    className="w-full truncate rounded-md px-2 py-1.5 text-left text-sm text-foreground/80 transition-colors hover:bg-muted hover:text-foreground"
                                >
                                    {article.title}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        );
    };

    return (
        <>
            <Head title={t('handbook.title')} />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={title}
                        description={t('handbook.description')}
                    />

                    {canManage && (
                        <AddArticleDialog chapters={sortedChapters} />
                    )}
                </div>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={t('handbook.search.placeholder')}
                        className="pl-9"
                        aria-label={t('handbook.search.placeholder')}
                    />
                    {isSearching && (
                        <button
                            type="button"
                            onClick={() => setQuery('')}
                            aria-label={t('handbook.search.clear')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    )}
                </div>

                {isSearching && (
                    <p className="text-sm text-muted-foreground">
                        {t('handbook.search.results', {
                            count: visibleArticles.length,
                        })}
                    </p>
                )}

                {!hasAnyArticles ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('handbook.empty')}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
                        <aside className="hidden lg:block">
                            <nav className="sticky top-4 flex max-h-[calc(100svh-2rem)] flex-col gap-4 overflow-y-auto overscroll-contain rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                                <div className="flex items-center justify-between gap-2 px-2">
                                    <p className="text-sm font-semibold">
                                        {t('handbook.toc.title')}
                                    </p>
                                    {hasVisibleArticles && !isSearching && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setCollapsedChapters(
                                                    allChaptersCollapsed
                                                        ? new Set()
                                                        : new Set(tocGroupKeys),
                                                )
                                            }
                                            className="text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                                        >
                                            {allChaptersCollapsed
                                                ? t('handbook.toc.expand_all')
                                                : t('handbook.toc.collapse_all')}
                                        </button>
                                    )}
                                </div>
                                {hasVisibleArticles ? (
                                    <div className="space-y-4">
                                        {sortedChapters.map((chapter) =>
                                            renderTocGroup(
                                                `${chapter.position}. ${chapter.title}`,
                                                articlesForChapter(chapter.id),
                                                chapter.id,
                                            ),
                                        )}
                                        {renderTocGroup(
                                            t('handbook.unassigned'),
                                            unassignedArticles,
                                            'unassigned',
                                        )}
                                    </div>
                                ) : (
                                    <p className="px-2 text-sm text-muted-foreground">
                                        {t('handbook.search.no_results', {
                                            query,
                                        })}
                                    </p>
                                )}
                            </nav>
                        </aside>

                        <main className="flex min-w-0 flex-col gap-12">
                            {!hasVisibleArticles ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('handbook.search.no_results', { query })}
                                </p>
                            ) : (
                                <>
                                    {sortedChapters.map((chapter) => {
                                        const chapterArticles =
                                            articlesForChapter(chapter.id);

                                        if (chapterArticles.length === 0) {
                                            return null;
                                        }

                                        return (
                                            <section
                                                key={chapter.id}
                                                className="space-y-8"
                                            >
                                                <h2 className="border-b border-border/60 pb-3 text-2xl font-semibold tracking-tight">
                                                    {chapter.position}.{' '}
                                                    {chapter.title}
                                                </h2>
                                                {chapterArticles.map(
                                                    (article) => (
                                                        <HandbookArticleSection
                                                            key={article.id}
                                                            article={article}
                                                            canManage={
                                                                canManage
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </section>
                                        );
                                    })}

                                    {unassignedArticles.length > 0 && (
                                        <section className="space-y-8">
                                            <h2 className="border-b border-border/60 pb-3 text-2xl font-semibold tracking-tight">
                                                {t('handbook.unassigned')}
                                            </h2>
                                            {unassignedArticles.map(
                                                (article) => (
                                                    <HandbookArticleSection
                                                        key={article.id}
                                                        article={article}
                                                        canManage={canManage}
                                                    />
                                                ),
                                            )}
                                        </section>
                                    )}
                                </>
                            )}
                        </main>
                    </div>
                )}
            </div>
        </>
    );
}

function HandbookArticleSection({
    article,
    canManage,
}: {
    article: ReaderArticle;
    canManage: boolean;
}) {
    const { t } = useTranslation();

    return (
        <section
            id={articleAnchorId(article.id)}
            className="scroll-mt-4 space-y-4"
        >
            {canManage && (
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={editArticle({ article: article.id })}
                            prefetch
                        >
                            <Pencil className="size-4" />
                            {t('handbook.actions.edit')}
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            if (
                                window.confirm(
                                    t('handbook.actions.delete_confirm'),
                                )
                            ) {
                                router.delete(
                                    destroyArticle({ article: article.id }).url,
                                    { preserveScroll: true },
                                );
                            }
                        }}
                    >
                        <Trash2 className="size-4" />
                        {t('handbook.actions.delete')}
                    </Button>
                </div>
            )}

            <IssueArticleReader
                title={article.title}
                content={article.content}
                editorSettings={article.editor_settings}
                mediaItems={article.media}
            />
        </section>
    );
}

function AddArticleDialog({ chapters }: { chapters: ReaderChapter[] }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [chapterId, setChapterId] = useState('none');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus className="size-4" />
                    {t('handbook.actions.add_article')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{t('handbook.add_dialog.title')}</DialogTitle>
                <DialogDescription>
                    {t('handbook.add_dialog.description')}
                </DialogDescription>

                <Form action={storeArticle()} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="handbook-article-title">
                                    {t('handbook.add_dialog.title_label')}
                                </Label>
                                <Input
                                    id="handbook-article-title"
                                    name="title"
                                    placeholder={t(
                                        'handbook.add_dialog.title_placeholder',
                                    )}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="handbook-article-chapter">
                                    {t('handbook.add_dialog.chapter_label')}
                                </Label>
                                <input
                                    type="hidden"
                                    name="publication_chapter_id"
                                    value={
                                        chapterId === 'none' ? '' : chapterId
                                    }
                                />
                                <Select
                                    value={chapterId}
                                    onValueChange={setChapterId}
                                >
                                    <SelectTrigger
                                        id="handbook-article-chapter"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            {t(
                                                'handbook.add_dialog.chapter_none',
                                            )}
                                        </SelectItem>
                                        {chapters.map((chapter) => (
                                            <SelectItem
                                                key={chapter.id}
                                                value={String(chapter.id)}
                                            >
                                                {chapter.position}.{' '}
                                                {chapter.title}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.publication_chapter_id}
                                />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        {t('handbook.add_dialog.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <Spinner className="size-4" />
                                    ) : (
                                        <Plus className="size-4" />
                                    )}
                                    {t('handbook.add_dialog.submit')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
