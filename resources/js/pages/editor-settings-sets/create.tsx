import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import EditorSettingsSetController from '@/actions/App/Http/Controllers/EditorSettingsSetController';
import EditorSettingsSetFormFields from '@/components/editor-settings-sets/editor-settings-set-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { translate } from '@/lib/i18n';
import { create, index } from '@/routes/editor-settings-sets';
import type { PublicationEditorFont } from '@/types';

export default function EditorSettingsSetsCreate() {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [font, setFont] = useState<PublicationEditorFont>('spectral');
    const [hasMarginalColumn, setHasMarginalColumn] = useState(true);

    return (
        <>
            <Head title={t('editor.settings_sets.create.head_title')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={t('editor.settings_sets.create.title')}
                    description={t('editor.settings_sets.create.description')}
                />

                <Form
                    {...EditorSettingsSetController.store.form()}
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

                            <div className="flex flex-wrap gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t('editor.settings_sets.create.submit')}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index()} prefetch>
                                        {t('common.cancel')}
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditorSettingsSetsCreate.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.editor_settings'),
            href: index(),
        },
        {
            title: translate(props.translations, 'common.new'),
            href: create(),
        },
    ],
});
