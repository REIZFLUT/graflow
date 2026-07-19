import { Head, Link } from '@inertiajs/react';
import { Eye, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { create, edit, index } from '@/routes/publications';
import { show as showReader } from '@/routes/publications/issues/reader';
import type { PaginatedPublications, Publication } from '@/types';

type PageProps = {
    publications: PaginatedPublications;
};

export default function PublicationsIndex({ publications }: PageProps) {
    const { t } = useTranslation();
    const [selectedPublicationId, setSelectedPublicationId] = useState<
        number | null
    >(null);

    const selectedPublication = useMemo(
        () =>
            publications.data.find(
                (publication) => publication.id === selectedPublicationId,
            ) ?? null,
        [publications.data, selectedPublicationId],
    );

    const openIssueDialog = (publication: Publication) => {
        setSelectedPublicationId(publication.id);
    };

    return (
        <>
            <Head title={t('publications.title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-6">
                    <Heading
                        title={t('publications.title')}
                        description={t('publications.description')}
                    />

                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus className="size-4" />
                            {t('publications.new')}
                        </Link>
                    </Button>
                </div>

                {publications.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('publications.empty')}
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create()} prefetch>
                                {t('publications.create_first')}
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('publications.table.name')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('publications.table.issues')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('publications.table.owner')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        {t('common.action')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {publications.data.map((publication) => {
                                    const canEdit =
                                        publication.can_edit !== false;

                                    return (
                                        <tr
                                            key={publication.id}
                                            className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {publication.name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {publication.issues_count ?? 0}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {canEdit
                                                    ? t('common.em_dash')
                                                    : t(
                                                          'publications.owned_by',
                                                          {
                                                              name:
                                                                  publication
                                                                      .owner
                                                                      ?.name ??
                                                                  t(
                                                                      'common.unknown',
                                                                  ),
                                                          },
                                                      )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap items-center justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        type="button"
                                                        onClick={() =>
                                                            openIssueDialog(
                                                                publication,
                                                            )
                                                        }
                                                    >
                                                        <Eye className="size-4" />
                                                        {t(
                                                            'publications.view_issue',
                                                        )}
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit({
                                                                publication:
                                                                    publication.id,
                                                            })}
                                                            prefetch
                                                        >
                                                            {canEdit
                                                                ? t(
                                                                      'common.edit',
                                                                  )
                                                                : t(
                                                                      'publications.view',
                                                                  )}
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog
                open={selectedPublicationId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedPublicationId(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {t('publications.reader.modal_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('publications.reader.select_issue')}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedPublication === null ||
                    (selectedPublication.issues?.length ?? 0) === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('publications.reader.no_issues')}
                        </p>
                    ) : (
                        <ul className="divide-y divide-border rounded-lg border border-border">
                            {selectedPublication.issues?.map((issue) => (
                                <li key={issue.id}>
                                    <a
                                        href={showReader.url({
                                            publication:
                                                selectedPublication.id,
                                            issue: issue.id,
                                        })}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block px-4 py-3 text-sm font-medium transition-colors hover:bg-muted/60"
                                        onClick={() =>
                                            setSelectedPublicationId(null)
                                        }
                                    >
                                        {issue.label}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

PublicationsIndex.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.publications'),
            href: index(),
        },
    ],
});
