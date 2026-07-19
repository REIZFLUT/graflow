import { useTranslation } from '@/hooks/use-translation';
import { getArticleStatusLabel } from '@/lib/article-status';
import { formatDateTime } from '@/lib/i18n';
import type { ArticleWorkflowEvent } from '@/types';

type WorkflowHistoryPanelProps = {
    events: ArticleWorkflowEvent[];
};

export default function WorkflowHistoryPanel({
    events,
}: WorkflowHistoryPanelProps) {
    const { t, locale } = useTranslation();

    if (events.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('articles.workflow.history_empty')}
            </p>
        );
    }

    return (
        <div className="space-y-3">
            {events.map((event) => {
                const toLabel = getArticleStatusLabel(event.to_status, t);
                const title =
                    event.from_status === null
                        ? t('articles.workflow.history.initial')
                        : t('articles.workflow.history.transition', {
                              from: getArticleStatusLabel(
                                  event.from_status,
                                  t,
                              ),
                              to: toLabel,
                          });

                return (
                    <div
                        key={event.id}
                        className="rounded-md border border-border/60 bg-muted/20 p-3"
                    >
                        <div className="min-w-0 space-y-1.5">
                            <p className="text-sm font-medium">{title}</p>
                            {event.from_status === null && (
                                <p className="text-xs text-muted-foreground">
                                    {toLabel}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                {event.actor.name} ·{' '}
                                {formatDateTime(event.created_at, locale)}
                                {event.assignee !== null &&
                                    ` · ${event.assignee.name}`}
                            </p>
                            {event.reason !== null && event.reason !== '' && (
                                <div className="space-y-0.5 pt-1">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        {t('articles.workflow.reason')}
                                    </p>
                                    <p className="text-sm whitespace-pre-wrap">
                                        {event.reason}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
