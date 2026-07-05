import { Form } from '@inertiajs/react';
import ArticleVersionController from '@/actions/App/Http/Controllers/ArticleVersionController';
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
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/i18n';
import type { ArticleVersion } from '@/types';

type VersionHistoryProps = {
    articleId: number;
    versions: ArticleVersion[];
    variant?: 'default' | 'compact';
};

export default function VersionHistory({
    articleId,
    versions,
    variant = 'default',
}: VersionHistoryProps) {
    const { t, locale } = useTranslation();

    if (versions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('articles.versions.empty')}
            </p>
        );
    }

    return (
        <div className={variant === 'compact' ? 'space-y-3 pt-4' : 'space-y-4'}>
            {versions.map((version) => (
                <div
                    key={version.id}
                    className={
                        variant === 'compact'
                            ? 'rounded-md border border-border/60 bg-muted/20 p-3'
                            : 'rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border'
                    }
                >
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 space-y-1.5">
                            <p className="text-sm font-medium">
                                {t('articles.versions.label', {
                                    number: version.version_number,
                                })}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {version.created_by?.name ??
                                    t('common.unknown')}{' '}
                                ·{' '}
                                {formatDateTime(version.created_at, locale)}
                            </p>
                            <p className="line-clamp-1 text-xs text-muted-foreground">
                                {version.title}
                            </p>
                        </div>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    variant={
                                        variant === 'compact'
                                            ? 'ghost'
                                            : 'outline'
                                    }
                                    size="sm"
                                    data-test={`restore-version-${version.id}`}
                                >
                                    {t('articles.versions.restore')}
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>
                                    {t('articles.versions.restore_title', {
                                        number: version.version_number,
                                    })}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('articles.versions.restore_description')}
                                </DialogDescription>

                                <Form
                                    {...ArticleVersionController.restore.form({
                                        article: articleId,
                                        version: version.id,
                                    })}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                >
                                    {({ processing }) => (
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    {t('common.cancel')}
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                disabled={processing}
                                                asChild
                                            >
                                                <button type="submit">
                                                    {t(
                                                        'articles.versions.restore',
                                                    )}
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            ))}
        </div>
    );
}
