import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

type DashboardStats = {
    articles: number;
    publications: number;
    editorSettingsSets?: number;
};

type DashboardPageProps = {
    stats: DashboardStats;
};

export default function Dashboard() {
    const { t } = useTranslation();
    const { auth, stats, can } = usePage<DashboardPageProps>().props;

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('nav.dashboard'),
                href: dashboard(),
            },
        ],
    });

    const statCards = [
        {
            key: 'articles',
            label: t('dashboard.stats.articles.label'),
            value: stats.articles,
            description: t('dashboard.stats.articles.description'),
        },
        {
            key: 'publications',
            label: t('dashboard.stats.publications.label'),
            value: stats.publications,
            description: t('dashboard.stats.publications.description'),
        },
        ...(can.manageEditorSettingsSets && stats.editorSettingsSets !== undefined
            ? [
                  {
                      key: 'editor_settings',
                      label: t('dashboard.stats.editor_settings.label'),
                      value: stats.editorSettingsSets,
                      description: t(
                          'dashboard.stats.editor_settings.description',
                      ),
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title={t('dashboard.title')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('dashboard.title')}
                    </h1>
                    <p className="text-muted-foreground">
                        {auth.user?.name
                            ? t('dashboard.welcome_with_name', {
                                  name: auth.user.name,
                              })
                            : t('dashboard.welcome')}
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {statCards.map((stat) => (
                        <Card key={stat.key}>
                            <CardHeader>
                                <CardDescription>{stat.label}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {stat.value}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {stat.description}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
