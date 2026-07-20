import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTranslation } from '@/hooks/use-translation';
import { edit, update } from '@/routes/notifications';

type Preference = {
    key: string;
    enabled: boolean;
};

export default function Notifications({
    preferences,
}: {
    preferences: Preference[];
}) {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.notifications.title'),
                href: edit(),
            },
        ],
    });

    const { data, setData, patch, processing } = useForm<{
        preferences: Record<string, boolean>;
    }>({
        preferences: Object.fromEntries(
            preferences.map((preference) => [
                preference.key,
                preference.enabled,
            ]),
        ),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('settings.notifications.title')} />

            <h1 className="sr-only">{t('settings.notifications.sr_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('settings.notifications.heading')}
                    description={t('settings.notifications.description')}
                />

                {preferences.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('settings.notifications.empty')}
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-4">
                            {preferences.map((preference) => (
                                <div
                                    key={preference.key}
                                    className="flex items-start justify-between gap-4"
                                >
                                    <Label
                                        htmlFor={`notification-${preference.key}`}
                                        className="font-normal leading-relaxed"
                                    >
                                        {t(
                                            `settings.notifications.types.${preference.key}`,
                                        )}
                                    </Label>

                                    <Switch
                                        id={`notification-${preference.key}`}
                                        checked={data.preferences[preference.key]}
                                        onCheckedChange={(checked) =>
                                            setData('preferences', {
                                                ...data.preferences,
                                                [preference.key]: checked,
                                            })
                                        }
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-notifications-button"
                            >
                                {t('settings.notifications.save')}
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}
