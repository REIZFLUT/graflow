import { Form } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PublicationCategoryController from '@/actions/App/Http/Controllers/PublicationCategoryController';
import InputError from '@/components/input-error';
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
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import type { Publication, PublicationCategory } from '@/types';

type PublicationCategoriesManagerProps = {
    publication: Publication;
    categories: PublicationCategory[];
};

export default function PublicationCategoriesManager({
    publication,
    categories,
}: PublicationCategoriesManagerProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-base font-medium">
                    {t('publications.categories.heading')}
                </h3>
                <p className="text-sm text-muted-foreground">
                    {t('publications.categories.description')}
                </p>
            </div>

            {categories.length > 0 ? (
                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-sm">
                        <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t('publications.categories.table.name')}
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    {t('publications.categories.table.actions')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {categories.map((category) => (
                                <CategoryRow
                                    key={category.id}
                                    publication={publication}
                                    category={category}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                    {t('publications.categories.empty')}
                </div>
            )}

            <Form
                {...PublicationCategoryController.store.form({
                    publication: publication.id,
                })}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-wrap items-end gap-3"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid min-w-[12rem] flex-1 gap-2">
                            <Label htmlFor="new-category-name">
                                {t('publications.categories.new_label')}
                            </Label>
                            <Input
                                id="new-category-name"
                                name="name"
                                placeholder={t(
                                    'publications.categories.placeholder',
                                )}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Plus className="size-4" />
                            )}
                            {t('publications.categories.add_button')}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}

function CategoryRow({
    publication,
    category,
}: {
    publication: Publication;
    category: PublicationCategory;
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);

    return (
        <tr className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border">
            <td className="px-4 py-3">
                {editing ? (
                    <Form
                        {...PublicationCategoryController.update.form({
                            publication: publication.id,
                            category: category.id,
                        })}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setEditing(false)}
                        className="flex flex-wrap items-center gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Input
                                    name="name"
                                    defaultValue={category.name}
                                    className="max-w-xs"
                                    required
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {t('publications.categories.save')}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setEditing(false)}
                                >
                                    {t('publications.categories.cancel')}
                                </Button>
                                <InputError message={errors.name} />
                            </>
                        )}
                    </Form>
                ) : (
                    <button
                        type="button"
                        className="font-medium hover:underline"
                        onClick={() => setEditing(true)}
                    >
                        {category.name}
                    </button>
                )}
            </td>
            <td className="px-4 py-3 text-right">
                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="ghost" size="sm">
                            <Trash2 className="size-4" />
                            {t('publications.categories.delete')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            {t('publications.categories.delete_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('publications.categories.delete_description')}
                        </DialogDescription>
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="outline">
                                    {t('publications.categories.cancel')}
                                </Button>
                            </DialogClose>
                            <Form
                                {...PublicationCategoryController.destroy.form({
                                    publication: publication.id,
                                    category: category.id,
                                })}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        {t('publications.categories.delete')}
                                    </Button>
                                )}
                            </Form>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </td>
        </tr>
    );
}
