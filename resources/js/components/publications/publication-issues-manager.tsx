import { Form, Link } from '@inertiajs/react';
import { CalendarRange, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PublicationIssueController from '@/actions/App/Http/Controllers/PublicationIssueController';
import { show as showPlanning } from '@/actions/App/Http/Controllers/PublicationIssuePlanningController';
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
import type { Publication, PublicationIssue } from '@/types';

type PublicationIssuesManagerProps = {
    publication: Publication;
    issues: PublicationIssue[];
};

export default function PublicationIssuesManager({
    publication,
    issues,
}: PublicationIssuesManagerProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-base font-medium">
                    {t('publications.issues.heading')}
                </h3>
                <p className="text-sm text-muted-foreground">
                    {t('publications.issues.description')}
                </p>
            </div>

            {issues.length > 0 ? (
                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-sm">
                        <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    {t('publications.issues.table.label')}
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    {t('publications.issues.table.actions')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {issues.map((issue) => (
                                <IssueRow
                                    key={issue.id}
                                    publication={publication}
                                    issue={issue}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                    {t('publications.issues.empty')}
                </div>
            )}

            <Form
                {...PublicationIssueController.store.form({
                    publication: publication.id,
                })}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-wrap items-end gap-3"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid min-w-[12rem] flex-1 gap-2">
                            <Label htmlFor="new-issue-label">
                                {t('publications.issues.new_label')}
                            </Label>
                            <Input
                                id="new-issue-label"
                                name="label"
                                placeholder={t(
                                    'publications.issues.placeholder',
                                )}
                                required
                            />
                            <InputError message={errors.label} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <Spinner className="size-4" />
                            ) : (
                                <Plus className="size-4" />
                            )}
                            {t('publications.issues.add_button')}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}

function IssueRow({
    publication,
    issue,
}: {
    publication: Publication;
    issue: PublicationIssue;
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);

    return (
        <tr className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border">
            <td className="px-4 py-3">
                {editing ? (
                    <Form
                        {...PublicationIssueController.update.form({
                            publication: publication.id,
                            issue: issue.id,
                        })}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setEditing(false)}
                        className="flex flex-wrap items-center gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Input
                                    name="label"
                                    defaultValue={issue.label}
                                    className="max-w-xs"
                                    required
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {t('publications.issues.save')}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setEditing(false)}
                                >
                                    {t('publications.issues.cancel')}
                                </Button>
                                <InputError message={errors.label} />
                            </>
                        )}
                    </Form>
                ) : (
                    <button
                        type="button"
                        className="font-medium hover:underline"
                        onClick={() => setEditing(true)}
                    >
                        {issue.label}
                    </button>
                )}
            </td>
            <td className="px-4 py-3 text-right">
                <div className="flex justify-end gap-1">
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={showPlanning({
                                publication: publication.id,
                                issue: issue.id,
                            })}
                            prefetch
                        >
                            <CalendarRange className="size-4" />
                            {t('publications.issues.planning')}
                        </Link>
                    </Button>
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="ghost" size="sm">
                                <Trash2 className="size-4" />
                                {t('publications.issues.delete')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                {t('publications.issues.delete_title')}
                            </DialogTitle>
                            <DialogDescription>
                                {t('publications.issues.delete_description')}
                            </DialogDescription>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="outline">
                                        {t('publications.issues.cancel')}
                                    </Button>
                                </DialogClose>
                                <Form
                                    {...PublicationIssueController.destroy.form(
                                        {
                                            publication: publication.id,
                                            issue: issue.id,
                                        },
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            {t('publications.issues.delete')}
                                        </Button>
                                    )}
                                </Form>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </td>
        </tr>
    );
}
