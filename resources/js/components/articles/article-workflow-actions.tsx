import { Form } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
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
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

type ControlledDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const ACTION_ORDER: ArticleWorkflowAction[] = [
    'submit_manuscript',
    'complete_editorial_work',
    'request_revision',
    'force_status',
    'assign_author',
    'assign_editorial',
    'recall',
    'mark_ready',
    'start_product_manager_correction',
    'complete_product_manager_correction',
    'publish',
    'unpublish',
];

const REASON_REQUIRED_ACTIONS: ArticleWorkflowAction[] = [
    'request_revision',
    'unpublish',
];

const DESTRUCTIVE_ACTIONS: ArticleWorkflowAction[] = ['publish', 'unpublish'];

const ASSIGNMENT_ACTIONS: ArticleWorkflowAction[] = [
    'assign_author',
    'assign_editorial',
];

type WorkflowActionDialogProps = ControlledDialogProps & {
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
    open,
    onOpenChange,
}: WorkflowActionDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(`articles.workflow.dialogs.${action}.title`)}
                    </DialogTitle>
                    <DialogDescription>
                        {t(`articles.workflow.dialogs.${action}.description`)}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
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

type AssignmentDialogProps = ControlledDialogProps & {
    action: 'assign_author' | 'assign_editorial';
    form: WorkflowForm;
    users: ArticleWorkflowUser[];
};

function AssignmentDialog({
    action,
    form,
    users,
    open,
    onOpenChange,
}: AssignmentDialogProps) {
    const { t } = useTranslation();
    const [assigneeId, setAssigneeId] = useState('');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(`articles.workflow.dialogs.${action}.title`)}
                    </DialogTitle>
                    <DialogDescription>
                        {t(`articles.workflow.dialogs.${action}.description`)}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
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

type ForceStatusDialogProps = ControlledDialogProps & {
    form: WorkflowForm;
    editorialStaff: ArticleWorkflowUser[];
};

function ForceStatusDialog({
    form,
    editorialStaff,
    open,
    onOpenChange,
}: ForceStatusDialogProps) {
    const { t } = useTranslation();
    const [status, setStatus] = useState<ArticleStatus>('planned');
    const [assigneeId, setAssigneeId] = useState('');
    const needsEditorialAssignee = status === 'editorial_work';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
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

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
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
    const { t } = useTranslation();
    const [activeAction, setActiveAction] =
        useState<ArticleWorkflowAction | null>(null);

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

        if (action === 'unpublish') {
            return capabilities.unpublish;
        }

        if (
            action === 'recall' ||
            action === 'start_product_manager_correction'
        ) {
            return true;
        }

        return capabilities.manage_workflow;
    };

    const visibleActions = ACTION_ORDER.filter(isAllowed);

    if (visibleActions.length === 0) {
        return null;
    }

    const workflowFormFor = (action: ArticleWorkflowAction): WorkflowForm => {
        const params = { article: articleId };

        switch (action) {
            case 'submit_manuscript':
                return ArticleWorkflowController.submitManuscript.form(params);
            case 'complete_editorial_work':
                return ArticleWorkflowController.completeEditorialWork.form(
                    params,
                );
            case 'request_revision':
                return ArticleWorkflowController.requestRevision.form(params);
            case 'force_status':
                return ArticleWorkflowController.forceStatus.form(params);
            case 'assign_author':
                return ArticleWorkflowController.assignAuthor.form(params);
            case 'assign_editorial':
                return ArticleWorkflowController.assignEditorial.form(params);
            case 'recall':
                return ArticleWorkflowController.recall.form(params);
            case 'mark_ready':
                return ArticleWorkflowController.markReady.form(params);
            case 'start_product_manager_correction':
                return ArticleWorkflowController.startProductManagerCorrection.form(
                    params,
                );
            case 'complete_product_manager_correction':
                return ArticleWorkflowController.completeProductManagerCorrection.form(
                    params,
                );
            case 'publish':
                return ArticleWorkflowController.publish.form(params);
            case 'unpublish':
                return ArticleWorkflowController.unpublish.form(params);
        }
    };

    const closeDialog = (open: boolean) => {
        if (!open) {
            setActiveAction(null);
        }
    };

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button type="button" size="sm" variant="outline">
                        {t('articles.workflow.actions_menu')}
                        <ChevronDown className="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="min-w-56">
                    {visibleActions.map((action) => (
                        <DropdownMenuItem
                            key={action}
                            variant={
                                DESTRUCTIVE_ACTIONS.includes(action)
                                    ? 'destructive'
                                    : 'default'
                            }
                            onSelect={() => setActiveAction(action)}
                        >
                            {t(`articles.workflow.actions.${action}`)}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            {activeAction !== null &&
                activeAction !== 'force_status' &&
                !ASSIGNMENT_ACTIONS.includes(activeAction) && (
                    <WorkflowActionDialog
                        action={activeAction}
                        form={workflowFormFor(activeAction)}
                        reasonRequired={REASON_REQUIRED_ACTIONS.includes(
                            activeAction,
                        )}
                        destructive={DESTRUCTIVE_ACTIONS.includes(activeAction)}
                        open
                        onOpenChange={closeDialog}
                    />
                )}

            {(activeAction === 'assign_author' ||
                activeAction === 'assign_editorial') && (
                <AssignmentDialog
                    action={activeAction}
                    users={
                        activeAction === 'assign_author'
                            ? authors
                            : editorialStaff
                    }
                    form={workflowFormFor(activeAction)}
                    open
                    onOpenChange={closeDialog}
                />
            )}

            {activeAction === 'force_status' && (
                <ForceStatusDialog
                    editorialStaff={editorialStaff}
                    form={workflowFormFor('force_status')}
                    open
                    onOpenChange={closeDialog}
                />
            )}
        </>
    );
}
