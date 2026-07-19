import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, translate } from '@/lib/i18n';
import { create, edit, index } from '@/routes/users';
import type { PaginatedUsers } from '@/types';

type PageProps = {
    users: PaginatedUsers;
};

export default function UsersIndex({ users }: PageProps) {
    const { t, locale } = useTranslation();
    const { auth } = usePage().props;

    return (
        <>
            <Head title={t('users.title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-6">
                    <Heading
                        title={t('users.title')}
                        description={t('users.description')}
                    />

                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus className="size-4" />
                            {t('users.new')}
                        </Link>
                    </Button>
                </div>

                {users.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            {t('users.empty')}
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create()} prefetch>
                                {t('users.create_first')}
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('users.table.name')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('users.table.email')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('users.table.role')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        {t('users.table.created_at')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        {t('common.action')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {t(`users.roles.${user.role}`)}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatDateTime(
                                                user.created_at,
                                                locale,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {auth.user?.id !== user.id ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit({
                                                            user: user.id,
                                                        })}
                                                        prefetch
                                                    >
                                                        {t('common.edit')}
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    {t('common.em_dash')}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {users.last_page > 1 && (
                    <nav className="flex flex-wrap gap-2">
                        {users.links.map((link, linkIndex) =>
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

UsersIndex.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.users'),
            href: index(),
        },
    ],
});
