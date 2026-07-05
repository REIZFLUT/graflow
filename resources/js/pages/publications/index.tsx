import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { create, edit, index } from '@/routes/publications';
import type { PaginatedPublications } from '@/types';

type PageProps = {
    publications: PaginatedPublications;
};

export default function PublicationsIndex({ publications }: PageProps) {
    const { t } = useTranslation();

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
                                    <th className="px-4 py-3 text-right font-medium">
                                        {t('common.action')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {publications.data.map((publication) => (
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
                                        <td className="px-4 py-3 text-right">
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
