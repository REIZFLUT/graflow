import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    destroy as destroyChapter,
    store as storeChapter,
    update as updateChapter,
} from '@/actions/App/Http/Controllers/PublicationChapterController';
import {
    show,
    store as storeArticle,
} from '@/actions/App/Http/Controllers/PublicationIssuePlanningController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { formatDateTime, translate } from '@/lib/i18n';
import { edit as editArticle } from '@/routes/articles';
import { edit as editPublication } from '@/routes/publications';
import type {
    Article,
    ArticleUser,
    PublicationChapter,
    PublicationIssue,
} from '@/types';

type PlanningIssue = PublicationIssue & {
    chapters: PublicationChapter[];
    articles: Article[];
};

type PageProps = {
    publication: {
        id: number;
        name: string;
    };
    issue: PlanningIssue;
    authors: ArticleUser[];
};

export default function PublicationIssuePlanning({
    publication,
    issue,
    authors,
}: PageProps) {
    const { t, locale } = useTranslation();
    const [authorId, setAuthorId] = useState('');
    const [chapterId, setChapterId] = useState('unassigned');
    const chapters = [...issue.chapters].sort(
        (first, second) => first.position - second.position,
    );
    const nextPosition =
        Math.max(0, ...chapters.map((chapter) => chapter.position)) + 1;

    const articlesForChapter = (id: number | null) =>
        issue.articles
            .filter((article) =>
                id === null
                    ? article.publication_chapter_id === null
                    : article.publication_chapter_id === id,
            )
            .sort(
                (first, second) =>
                    first.position - second.position || first.id - second.id,
            );

    return (
        <>
            <Head
                title={t('publications.planning.head_title', {
                    issue: issue.label,
                })}
            />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-6">
                    <Heading
                        title={t('publications.planning.title', {
                            issue: issue.label,
                        })}
                        description={t('publications.planning.description', {
                            publication: publication.name,
                        })}
                    />
                    <Button variant="outline" asChild>
                        <Link
                            href={editPublication({
                                publication: publication.id,
                            })}
                            prefetch
                        >
                            {t('publications.planning.back_to_publication')}
                        </Link>
                    </Button>
                </div>

                <section className="space-y-4">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {t('publications.planning.chapters.heading')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {t('publications.planning.chapters.description')}
                        </p>
                    </div>

                    {chapters.length > 0 ? (
                        <div className="space-y-3">
                            {chapters.map((chapter) => (
                                <ChapterRow
                                    key={chapter.id}
                                    publicationId={publication.id}
                                    issueId={issue.id}
                                    chapter={chapter}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                            {t('publications.planning.chapters.empty')}
                        </div>
                    )}

                    <Form
                        action={storeChapter({
                            publication: publication.id,
                            issue: issue.id,
                        })}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex flex-wrap items-end gap-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid min-w-56 flex-1 gap-2">
                                    <Label htmlFor="new-chapter-title">
                                        {t(
                                            'publications.planning.chapters.new_title',
                                        )}
                                    </Label>
                                    <Input
                                        id="new-chapter-title"
                                        name="title"
                                        required
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="grid w-28 gap-2">
                                    <Label htmlFor="new-chapter-position">
                                        {t(
                                            'publications.planning.chapters.position',
                                        )}
                                    </Label>
                                    <Input
                                        id="new-chapter-position"
                                        name="position"
                                        type="number"
                                        min="1"
                                        defaultValue={nextPosition}
                                        required
                                    />
                                    <InputError message={errors.position} />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <Spinner className="size-4" />
                                    ) : (
                                        <Plus className="size-4" />
                                    )}
                                    {t(
                                        'publications.planning.chapters.add_button',
                                    )}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-4">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {t('publications.planning.new_article.heading')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {t('publications.planning.new_article.description')}
                        </p>
                    </div>

                    <Form
                        action={storeArticle({
                            publication: publication.id,
                            issue: issue.id,
                        })}
                        resetOnSuccess
                        onSuccess={() => {
                            setAuthorId('');
                            setChapterId('unassigned');
                        }}
                        className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-2 xl:grid-cols-6 dark:border-sidebar-border"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2 xl:col-span-2">
                                    <Label htmlFor="article-title">
                                        {t(
                                            'publications.planning.new_article.title',
                                        )}
                                    </Label>
                                    <Input
                                        id="article-title"
                                        name="title"
                                        required
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="article-author">
                                        {t(
                                            'publications.planning.new_article.author',
                                        )}
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="author_id"
                                        value={authorId}
                                    />
                                    <Select
                                        value={authorId}
                                        onValueChange={setAuthorId}
                                        required
                                    >
                                        <SelectTrigger
                                            id="article-author"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={t(
                                                    'publications.planning.new_article.select_author',
                                                )}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {authors.map((author) => (
                                                <SelectItem
                                                    key={author.id}
                                                    value={String(author.id)}
                                                >
                                                    {author.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.author_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="article-chapter">
                                        {t(
                                            'publications.planning.new_article.chapter',
                                        )}
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="publication_chapter_id"
                                        value={
                                            chapterId === 'unassigned'
                                                ? ''
                                                : chapterId
                                        }
                                    />
                                    <Select
                                        value={chapterId}
                                        onValueChange={setChapterId}
                                    >
                                        <SelectTrigger
                                            id="article-chapter"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="unassigned">
                                                {t(
                                                    'publications.planning.unassigned',
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

                                <div className="grid gap-2">
                                    <Label htmlFor="article-position">
                                        {t(
                                            'publications.planning.new_article.position',
                                        )}
                                    </Label>
                                    <Input
                                        id="article-position"
                                        name="position"
                                        type="number"
                                        min="1"
                                        defaultValue={1}
                                        required
                                    />
                                    <InputError message={errors.position} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="submission-deadline">
                                        {t(
                                            'publications.planning.new_article.deadline',
                                        )}
                                    </Label>
                                    <Input
                                        id="submission-deadline"
                                        name="submission_deadline"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        message={errors.submission_deadline}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="target-character-count">
                                        {t(
                                            'publications.planning.new_article.target_count',
                                        )}
                                    </Label>
                                    <Input
                                        id="target-character-count"
                                        name="target_character_count"
                                        type="number"
                                        min="1"
                                        required
                                    />
                                    <InputError
                                        message={errors.target_character_count}
                                    />
                                </div>

                                <div className="flex items-end md:col-span-2 xl:col-span-6">
                                    <Button
                                        type="submit"
                                        disabled={processing || authorId === ''}
                                    >
                                        {processing && (
                                            <Spinner className="size-4" />
                                        )}
                                        {t(
                                            'publications.planning.new_article.submit',
                                        )}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-5">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {t('publications.planning.articles.heading')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {t('publications.planning.articles.description')}
                        </p>
                    </div>

                    {chapters.map((chapter) => (
                        <ArticleGroup
                            key={chapter.id}
                            heading={`${chapter.position}. ${chapter.title}`}
                            articles={articlesForChapter(chapter.id)}
                            locale={locale}
                        />
                    ))}
                    <ArticleGroup
                        heading={t('publications.planning.unassigned')}
                        articles={articlesForChapter(null)}
                        locale={locale}
                    />
                </section>
            </div>
        </>
    );
}

function ChapterRow({
    publicationId,
    issueId,
    chapter,
}: {
    publicationId: number;
    issueId: number;
    chapter: PublicationChapter;
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);
    const routeArguments = {
        publication: publicationId,
        issue: issueId,
        chapter: chapter.id,
    };

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            {editing ? (
                <Form
                    action={updateChapter(routeArguments)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="flex min-w-0 flex-1 flex-wrap items-start gap-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="min-w-52 flex-1">
                                <Input
                                    name="title"
                                    defaultValue={chapter.title}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="w-24">
                                <Input
                                    name="position"
                                    type="number"
                                    min="1"
                                    defaultValue={chapter.position}
                                    aria-label={t(
                                        'publications.planning.chapters.position',
                                    )}
                                    required
                                />
                                <InputError message={errors.position} />
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                {t('common.save')}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setEditing(false)}
                            >
                                {t('common.cancel')}
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <div className="flex min-w-0 items-center gap-3">
                    <Badge variant="outline">{chapter.position}</Badge>
                    <span className="truncate font-medium">
                        {chapter.title}
                    </span>
                </div>
            )}

            {!editing && (
                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setEditing(true)}
                    >
                        <Pencil className="size-4" />
                        {t('common.edit')}
                    </Button>
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="ghost" size="sm">
                                <Trash2 className="size-4" />
                                {t('common.delete')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                {t(
                                    'publications.planning.chapters.delete_title',
                                )}
                            </DialogTitle>
                            <DialogDescription>
                                {t(
                                    'publications.planning.chapters.delete_description',
                                )}
                            </DialogDescription>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="outline">
                                        {t('common.cancel')}
                                    </Button>
                                </DialogClose>
                                <Form action={destroyChapter(routeArguments)}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            {t('common.delete')}
                                        </Button>
                                    )}
                                </Form>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            )}
        </div>
    );
}

function ArticleGroup({
    heading,
    articles,
    locale,
}: {
    heading: string;
    articles: Article[];
    locale: string;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2">
                <h3 className="font-medium">{heading}</h3>
                <Badge variant="secondary">{articles.length}</Badge>
            </div>

            {articles.length === 0 ? (
                <p className="rounded-lg border border-dashed border-sidebar-border/70 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    {t('publications.planning.articles.empty_group')}
                </p>
            ) : (
                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full min-w-4xl text-sm">
                        <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                            <tr>
                                <th className="w-24 px-4 py-3 text-left font-medium">
                                    {t(
                                        'publications.planning.articles.position',
                                    )}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t('publications.planning.articles.title')}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t('publications.planning.articles.status')}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t('publications.planning.articles.author')}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t(
                                        'publications.planning.articles.assignee',
                                    )}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t(
                                        'publications.planning.articles.deadline',
                                    )}
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t(
                                        'publications.planning.articles.target_count',
                                    )}
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    {t('common.action')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {articles.map((article) => (
                                <tr
                                    key={article.id}
                                    className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                >
                                    <td className="px-4 py-3">
                                        <Badge>{article.position}</Badge>
                                    </td>
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
                                                href={editArticle({
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
    );
}

PublicationIssuePlanning.layout = (props: {
    translations: Record<string, unknown>;
    publication: PageProps['publication'];
    issue: PlanningIssue;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.publications'),
            href: editPublication({ publication: props.publication.id }),
        },
        {
            title: props.issue.label,
            href: show({
                publication: props.publication.id,
                issue: props.issue.id,
            }),
        },
    ],
});
