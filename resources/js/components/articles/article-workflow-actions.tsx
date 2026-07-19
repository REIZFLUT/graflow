import { Form } from '@inertiajs/react';
import { useState } from 'react';
import ArticleWorkflowController from '@/actions/App/Http/Controllers/ArticleWorkflowController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import type {
    ArticleCapabilities,
    ArticleStatus,
    ArticleWorkflowAction,
    ArticleWorkflowUser,
} from '@/types';

type ArticleWorkflowActionsProps = {
    articleId: number;
    capabilities: ArticleCapabilities;
    allowedActions: ArticleWorkflowAction[];
    authors: ArticleWorkflowUser[];
    editorialStaff: ArticleWorkflowUser[];
};

type WorkflowForm = {
    action: string;
    method: 'post';
};

type WorkflowActionDialogProps = {
    action: ArticleWorkflowAction;
    form: WorkflowForm;
    reasonRequired?: boolean;
    destructive?: boolean;
};

function WorkflowActionDialog({
    action,
    form,
    reasonRequired = false,
    destructive = false,
}: WorkflowActionDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    variant={destructive ? 'destructive' : 'outline'}
                >
                    {t(`articles.workflow.actions.${action}`)}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(`articles.workflow.dialogs.${action}.title`)}
                    </DialogTitle>
                    <DialogDescription>
                        {t(`articles.workflow.dialogs.${action}.description`)}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form} options={{ preserveScroll: true }}>
                    {({ errors, processing }) => (
                        <div className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor={`workflow-reason-${action}`}>
                                    {t('articles.workflow.reason')}
                                    {!reasonRequired &&
                                        ` ${t('articles.workflow.optional')}`}
                                </Label>
                                <textarea
                                    id={`workflow-reason-${action}`}
                                    name="reason"
                                    required={reasonRequired}
                                    rows={4}
                                    className="min-h-24 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                                    placeholder={t(
                                        'articles.workflow.reason_placeholder',
                                    )}
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('common.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                >
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t(`articles.workflow.actions.${action}`)}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function AssignmentDialog({
    action,
    form,
    users,
}: {
    action: 'assign_author' | 'assign_editorial';
    form: WorkflowForm;
    users: ArticleWorkflowUser[];
}) {
    const { t } = useTranslation();
    const [assigneeId, setAssigneeId] = useState('');

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" size="sm" variant="outline">
                    {t(`articles.workflow.actions.${action}`)}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(`articles.workflow.dialogs.${action}.title`)}
                    </DialogTitle>
                    <DialogDescription>
                        {t(`articles.workflow.dialogs.${action}.description`)}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form} options={{ preserveScroll: true }}>
                    {({ errors, processing }) => (
                        <div className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor={`workflow-assignee-${action}`}>
                                    {t('articles.workflow.assignee')}
                                </Label>
                                <input
                                    type="hidden"
                                    name="assignee_id"
                                    value={assigneeId}
                                />
                                <Select
                                    value={assigneeId}
                                    onValueChange={setAssigneeId}
                                    required
                                >
                                    <SelectTrigger
                                        id={`workflow-assignee-${action}`}
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'articles.workflow.assignee_placeholder',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={String(user.id)}
                                            >
                                                {user.name} ·{' '}
                                                {t(
                                                    `articles.workflow.roles.${user.role}`,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.assignee_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`workflow-reason-${action}`}>
                                    {t('articles.workflow.reason')}{' '}
                                    {t('articles.workflow.optional')}
                                </Label>
                                <textarea
                                    id={`workflow-reason-${action}`}
                                    name="reason"
                                    rows={3}
                                    className="min-h-20 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                                    placeholder={t(
                                        'articles.workflow.reason_placeholder',
                                    )}
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('common.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing || assigneeId === ''}
                                >
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t(`articles.workflow.actions.${action}`)}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

const articleStatuses: ArticleStatus[] = [
    'planned',
    'authoring',
    'manuscript_submitted',
    'product_manager_correction',
    'revision_requested',
    'revision',
    'editorial_work',
    'ready_for_publication',
    'published',
];

function ForceStatusDialog({
    form,
    editorialStaff,
}: {
    form: WorkflowForm;
    editorialStaff: ArticleWorkflowUser[];
}) {
    const { t } = useTranslation();
    const [status, setStatus] = useState<ArticleStatus>('planned');
    const [assigneeId, setAssigneeId] = useState('');
    const needsEditorialAssignee = status === 'editorial_work';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" size="sm" variant="outline">
                    {t('articles.workflow.actions.force_status')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('articles.workflow.dialogs.force_status.title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'articles.workflow.dialogs.force_status.description',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form} options={{ preserveScroll: true }}>
                    {({ errors, processing }) => (
                        <div className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="force-article-status">
                                    {t('articles.workflow.status')}
                                </Label>
                                <input
                                    type="hidden"
                                    name="status"
                                    value={status}
                                />
                                <Select
                                    value={status}
                                    onValueChange={(value) => {
                                        setStatus(value as ArticleStatus);
                                        setAssigneeId('');
                                    }}
                                >
                                    <SelectTrigger
                                        id="force-article-status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {articleStatuses.map(
                                            (articleStatus) => (
                                                <SelectItem
                                                    key={articleStatus}
                                                    value={articleStatus}
                                                >
                                                    {t(
                                                        `articles.status.${articleStatus}`,
                                                    )}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            {needsEditorialAssignee && (
                                <div className="grid gap-2">
                                    <Label htmlFor="force-status-assignee">
                                        {t('articles.workflow.assignee')}
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="assignee_id"
                                        value={assigneeId}
                                    />
                                    <Select
                                        value={assigneeId}
                                        onValueChange={setAssigneeId}
                                        required
                                    >
                                        <SelectTrigger
                                            id="force-status-assignee"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={t(
                                                    'articles.workflow.assignee_placeholder',
                                                )}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {editorialStaff.map((user) => (
                                                <SelectItem
                                                    key={user.id}
                                                    value={String(user.id)}
                                                >
                                                    {user.name} ·{' '}
                                                    {t(
                                                        `articles.workflow.roles.${user.role}`,
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.assignee_id} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="force-status-reason">
                                    {t('articles.workflow.reason')}{' '}
                                    {t('articles.workflow.optional')}
                                </Label>
                                <textarea
                                    id="force-status-reason"
                                    name="reason"
                                    rows={3}
                                    className="min-h-20 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                                />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('common.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        (needsEditorialAssignee &&
                                            assigneeId === '')
                                    }
                                >
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t(
                                        'articles.workflow.actions.force_status',
                                    )}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ArticleWorkflowActions({
    articleId,
    capabilities,
    allowedActions,
    authors,
    editorialStaff,
}: ArticleWorkflowActionsProps) {
    const isAllowed = (action: ArticleWorkflowAction): boolean => {
        if (!allowedActions.includes(action)) {
            return false;
        }

        if (action === 'submit_manuscript') {
            return capabilities.submit_manuscript;
        }

        if (action === 'complete_editorial_work') {
            return capabilities.complete_editorial_work;
        }

        if (action === 'request_revision') {
            return capabilities.request_revision;
        }

        if (action === 'force_status') {
            return capabilities.force_status;
        }

        return capabilities.manage_workflow;
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            {isAllowed('submit_manuscript') && (
                <WorkflowActionDialog
                    action="submit_manuscript"
                    form={ArticleWorkflowController.submitManuscript.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('complete_editorial_work') && (
                <WorkflowActionDialog
                    action="complete_editorial_work"
                    form={ArticleWorkflowController.completeEditorialWork.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('request_revision') && (
                <WorkflowActionDialog
                    action="request_revision"
                    reasonRequired
                    form={ArticleWorkflowController.requestRevision.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('force_status') && (
                <ForceStatusDialog
                    editorialStaff={editorialStaff}
                    form={ArticleWorkflowController.forceStatus.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('assign_author') && (
                <AssignmentDialog
                    action="assign_author"
                    users={authors}
                    form={ArticleWorkflowController.assignAuthor.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('assign_editorial') && (
                <AssignmentDialog
                    action="assign_editorial"
                    users={editorialStaff}
                    form={ArticleWorkflowController.assignEditorial.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('recall') && (
                <WorkflowActionDialog
                    action="recall"
                    form={ArticleWorkflowController.recall.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('mark_ready') && (
                <WorkflowActionDialog
                    action="mark_ready"
                    form={ArticleWorkflowController.markReady.form({
                        article: articleId,
                    })}
                />
            )}
            {isAllowed('start_product_manager_correction') && (
                <WorkflowActionDialog
                    action="start_product_manager_correction"
                    form={ArticleWorkflowController.startProductManagerCorrection.form(
                        {
                            article: articleId,
                        },
                    )}
                />
            )}
            {isAllowed('complete_product_manager_correction') && (
                <WorkflowActionDialog
                    action="complete_product_manager_correction"
                    form={ArticleWorkflowController.completeProductManagerCorrection.form(
                        {
                            article: articleId,
                        },
                    )}
                />
            )}
            {isAllowed('publish') && (
                <WorkflowActionDialog
                    action="publish"
                    destructive
                    form={ArticleWorkflowController.publish.form({
                        article: articleId,
                    })}
                />
            )}
        </div>
    );
}
