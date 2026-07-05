import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import PublicationController from '@/actions/App/Http/Controllers/PublicationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { translate } from '@/lib/i18n';
import { create as createEditorSettingsSet } from '@/routes/editor-settings-sets';
import { create, index } from '@/routes/publications';
import { formatEditorSettingsSetSummary } from '@/types';
import type { EditorSettingsSet } from '@/types';

type PageProps = {
    editorSettingsSets: EditorSettingsSet[];
};

export default function PublicationsCreate({
    editorSettingsSets,
}: PageProps) {
    const { t } = useTranslation();
    const [editorSettingsSetId, setEditorSettingsSetId] = useState(
        editorSettingsSets[0] ? String(editorSettingsSets[0].id) : '',
    );

    return (
        <>
            <Head title={t('publications.new')} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={t('publications.new')}
                    description={t('publications.create.description')}
                />

                <Form
                    {...PublicationController.store.form()}
                    className="max-w-lg space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('common.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder={t(
                                        'publications.create.name_placeholder',
                                    )}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <p className="font-medium">
                                        {t(
                                            'publications.create.editor_settings_heading',
                                        )}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'publications.create.editor_settings_description',
                                        )}
                                    </p>
                                </div>

                                <input
                                    type="hidden"
                                    name="editor_settings_set_id"
                                    value={editorSettingsSetId}
                                />

                                {editorSettingsSets.length > 0 ? (
                                    <div className="grid gap-2">
                                        <Label htmlFor="editor_settings_set_id">
                                            {t('common.set')}
                                        </Label>
                                        <Select
                                            value={editorSettingsSetId}
                                            onValueChange={setEditorSettingsSetId}
                                        >
                                            <SelectTrigger id="editor_settings_set_id">
                                                <SelectValue
                                                    placeholder={t(
                                                        'common.select_set',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {editorSettingsSets.map(
                                                    (set) => (
                                                        <SelectItem
                                                            key={set.id}
                                                            value={String(
                                                                set.id,
                                                            )}
                                                        >
                                                            {set.name} (
                                                            {formatEditorSettingsSetSummary(
                                                                set,
                                                                t,
                                                            )}
                                                            )
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                errors.editor_settings_set_id
                                            }
                                        />
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground dark:border-sidebar-border">
                                        <p>
                                            {t(
                                                'publications.create.no_sets_hint',
                                            )}
                                        </p>
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="mt-3"
                                        >
                                            <Link
                                                href={createEditorSettingsSet()}
                                                prefetch
                                            >
                                                {t('common.create_set')}
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        editorSettingsSets.length === 0 ||
                                        editorSettingsSetId === ''
                                    }
                                >
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t('publications.create.submit')}
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

PublicationsCreate.layout = (props: {
    translations: Record<string, unknown>;
}) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.publications'),
            href: index(),
        },
        {
            title: translate(props.translations, 'common.new'),
            href: create(),
        },
    ],
});
