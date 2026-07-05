import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import PublicationController from '@/actions/App/Http/Controllers/PublicationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PublicationCategoriesManager from '@/components/publications/publication-categories-manager';
import PublicationIssuesManager from '@/components/publications/publication-issues-manager';
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
import { edit, index } from '@/routes/publications';
import { formatEditorSettingsSetSummary } from '@/types';
import type {
    EditorSettingsSet,
    Publication,
    PublicationCategory,
    PublicationIssue,
} from '@/types';

type PageProps = {
    publication: Publication & {
        issues: PublicationIssue[];
        categories: PublicationCategory[];
    };
    editorSettingsSets: EditorSettingsSet[];
};

export default function PublicationsEdit({
    publication,
    editorSettingsSets,
}: PageProps) {
    const { t } = useTranslation();
    const [editorSettingsSetId, setEditorSettingsSetId] = useState(
        String(publication.editor_settings_set_id ?? ''),
    );

    return (
        <>
            <Head title={publication.name} />

            <div className="flex flex-col gap-8 p-4 md:p-6 lg:p-8">
                <Heading
                    title={publication.name}
                    description={t('publications.edit.description')}
                />

                <Form
                    {...PublicationController.update.form({
                        publication: publication.id,
                    })}
                    options={{ preserveScroll: true }}
                    className="max-w-lg space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('common.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={publication.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-4 border-t border-sidebar-border/70 pt-6 dark:border-sidebar-border">
                                <div>
                                    <p className="font-medium">
                                        {t(
                                            'publications.create.editor_settings_heading',
                                        )}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'publications.edit.editor_settings_description',
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

                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    editorSettingsSets.length === 0 ||
                                    editorSettingsSetId === ''
                                }
                            >
                                {processing && <Spinner className="size-4" />}
                                {t('common.save')}
                            </Button>
                        </>
                    )}
                </Form>

                <PublicationIssuesManager
                    publication={publication}
                    issues={publication.issues}
                />

                <PublicationCategoriesManager
                    publication={publication}
                    categories={publication.categories}
                />

                <div className="border-t border-sidebar-border/70 pt-8 dark:border-sidebar-border">
                    <Form
                        {...PublicationController.destroy.form({
                            publication: publication.id,
                        })}
                    >
                        {({ processing }) => (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="font-medium">
                                        {t(
                                            'publications.edit.delete_heading',
                                        )}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'publications.edit.delete_description',
                                        )}
                                    </p>
                                </div>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
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

PublicationsEdit.layout = (props: { translations: Record<string, unknown> }) => ({
    breadcrumbs: [
        {
            title: translate(props.translations, 'nav.publications'),
            href: index(),
        },
        {
            title: translate(props.translations, 'common.edit'),
            href: edit({ publication: 0 }),
        },
    ],
});
