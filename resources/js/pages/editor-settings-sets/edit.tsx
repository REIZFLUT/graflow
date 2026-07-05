import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import EditorSettingsSetController from '@/actions/App/Http/Controllers/EditorSettingsSetController';
import EditorSettingsSetFormFields from '@/components/editor-settings-sets/editor-settings-set-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { edit, index } from '@/routes/editor-settings-sets';
import { formatEditorSettingsSetSummary } from '@/types';
import type { EditorSettingsSet, PublicationEditorFont } from '@/types';

type PageProps = {
    editorSettingsSet: EditorSettingsSet;
};

export default function EditorSettingsSetsEdit({
    editorSettingsSet,
}: PageProps) {
    const { t } = useTranslation();
    const [name, setName] = useState(editorSettingsSet.name);
    const [font, setFont] = useState<PublicationEditorFont>(
        editorSettingsSet.font,
    );
    const [hasMarginalColumn, setHasMarginalColumn] = useState(
        editorSettingsSet.has_marginal_column,
    );

    const inUseCount =
        (editorSettingsSet.publications_count ?? 0) +
        (editorSettingsSet.articles_count ?? 0);

    return (
        <>
            <Head title={editorSettingsSet.name} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={editorSettingsSet.name}
                    description={formatEditorSettingsSetSummary(
                        editorSettingsSet,
                        t,
                    )}
                />

                <Form
                    {...EditorSettingsSetController.update.form({
                        editor_settings_set: editorSettingsSet.id,
                    })}
                    options={{ preserveScroll: true }}
                    className="max-w-lg space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <EditorSettingsSetFormFields
                                name={name}
                                font={font}
                                hasMarginalColumn={hasMarginalColumn}
                                onNameChange={setName}
                                onFontChange={setFont}
                                onHasMarginalColumnChange={setHasMarginalColumn}
                                errors={errors}
                            />

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner className="size-4" />}
                                {t('common.save')}
                            </Button>
                        </>
                    )}
                </Form>

                <div className="border-t border-sidebar-border/70 pt-8 dark:border-sidebar-border">
                    <Form
                        {...EditorSettingsSetController.destroy.form({
                            editor_settings_set: editorSettingsSet.id,
                        })}
                    >
                        {({ processing }) => (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="font-medium">
                                        {t(
                                            'editor.settings_sets.delete_heading',
                                        )}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {inUseCount > 0
                                            ? t(
                                                  'editor.settings_sets.delete_in_use',
                                                  { count: inUseCount },
                                              )
                                            : t(
                                                  'editor.settings_sets.delete_description',
                                              )}
                                    </p>
                                </div>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing || inUseCount > 0}
                                >
                                    {t('common.delete')}
                                </Button>
                            </div>
                        )}
                    </Form>
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()} prefetch>
                        {t('common.back_to_overview')}
                    </Link>
                </Button>
            </div>
        </>
    );
}

EditorSettingsSetsEdit.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.editor_settings'),
            href: index(),
        },
        {
            title: translate(
                props.translations,
                'editor.settings_sets.edit.breadcrumb',
            ),
            href: edit({ editor_settings_set: 0 }),
        },
    ],
});
