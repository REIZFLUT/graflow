import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { AlertTriangle, Check, Sparkles, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { ProofreadErrorReason } from '@/hooks/use-proofread';
import { useTranslation } from '@/hooks/use-translation';
import { focusProofreadIssue, getProofreadIssues } from '@/lib/tiptap';
import type { MappedProofreadIssue, ProofreadCategory } from '@/lib/tiptap';
import { cn } from '@/lib/utils';

type ProofreadPanelProps = {
    editor: Editor | null;
    hasRun: boolean;
    isChecking?: boolean;
    error?: ProofreadErrorReason | null;
    focusedIssueId?: string | null;
    onFocusIssue?: (issue: MappedProofreadIssue) => void;
    onStartCheck?: () => void;
    className?: string;
};

const ERROR_TRANSLATION_KEYS: Record<ProofreadErrorReason, string> = {
    not_configured: 'editor.proofread.error_not_configured',
    unavailable: 'editor.proofread.error_unavailable',
    failed: 'editor.proofread.error',
    generic: 'editor.proofread.error',
};

const CATEGORY_TRANSLATION_KEYS: Record<ProofreadCategory, string> = {
    unfinished_sentence: 'editor.proofread.categories.unfinished_sentence',
    illogical_sentence: 'editor.proofread.categories.illogical_sentence',
    word_repetition: 'editor.proofread.categories.word_repetition',
    colloquialism: 'editor.proofread.categories.colloquialism',
    language_pattern: 'editor.proofread.categories.language_pattern',
    other: 'editor.proofread.categories.other',
};

export default function ProofreadPanel({
    editor,
    hasRun,
    isChecking = false,
    error = null,
    focusedIssueId = null,
    onFocusIssue,
    onStartCheck,
    className,
}: ProofreadPanelProps) {
    const { t } = useTranslation();
    const issues =
        useEditorState({
            editor,
            selector: ({ editor: currentEditor }) =>
                getProofreadIssues(currentEditor),
        }) ?? [];

    if (!editor) {
        return null;
    }

    const handleFocus = (issue: MappedProofreadIssue) => {
        focusProofreadIssue(editor, issue);
        onFocusIssue?.(issue);
    };

    return (
        <div className={cn('space-y-4', className)}>
            <p className="text-xs text-muted-foreground">
                {t('editor.proofread.description')}
            </p>

            {onStartCheck && (
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    className="w-full"
                    disabled={isChecking}
                    onClick={onStartCheck}
                >
                    {isChecking ? (
                        <Spinner className="size-4" />
                    ) : (
                        <Sparkles className="size-4" />
                    )}
                    {isChecking
                        ? t('editor.proofread.checking')
                        : t('editor.proofread.start')}
                </Button>
            )}

            {!isChecking && error && (
                <div
                    role="alert"
                    className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-sm text-destructive"
                >
                    <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                    <span>{t(ERROR_TRANSLATION_KEYS[error])}</span>
                </div>
            )}

            {!isChecking && !error && !hasRun && (
                <p className="text-sm text-muted-foreground">
                    {t('editor.proofread.not_run')}
                </p>
            )}

            {!isChecking && !error && hasRun && issues.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    {t('editor.proofread.empty')}
                </p>
            )}

            {!isChecking && !error && issues.length > 0 && (
                <ol className="space-y-4">
                    {issues.map((issue, index) => {
                        const isFocused = issue.id === focusedIssueId;
                        const isLocated =
                            issue.from !== null && issue.to !== null;

                        return (
                            <li
                                key={issue.id}
                                data-proofread-item={issue.id}
                                className={cn(
                                    'space-y-2 rounded-md border-b border-border/50 pb-4 transition-colors last:border-b-0 last:pb-0',
                                    isFocused &&
                                        'border-border bg-muted/50 px-3 py-3 ring-1 ring-border/60',
                                )}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex min-w-0 flex-wrap items-center gap-1.5">
                                        <span className="text-xs font-medium text-muted-foreground">
                                            {index + 1}.
                                        </span>
                                        <Badge
                                            variant="secondary"
                                            className={cn(
                                                'text-[0.65rem] font-medium',
                                                issue.severity === 'warning'
                                                    ? 'text-amber-700 dark:text-amber-400'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {t(
                                                CATEGORY_TRANSLATION_KEYS[
                                                    issue.category
                                                ],
                                            )}
                                        </Badge>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                                        onClick={() => {
                                            editor.commands.dismissProofreadIssue(
                                                issue.id,
                                            );
                                        }}
                                    >
                                        <X className="size-3.5" />
                                        {t('editor.proofread.dismiss')}
                                    </Button>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => handleFocus(issue)}
                                    className="w-full text-left text-sm leading-relaxed text-foreground transition-colors hover:text-foreground/80"
                                >
                                    {issue.message}
                                </button>
                                {issue.quote.length > 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        „{issue.quote}“
                                    </p>
                                )}
                                {issue.suggestion.length > 0 &&
                                    (isLocated ? (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            className="h-auto max-w-full justify-start whitespace-normal px-2 py-1 text-left text-xs"
                                            onClick={() => {
                                                handleFocus(issue);
                                                editor.commands.applyProofreadSuggestion(
                                                    issue.id,
                                                    issue.suggestion,
                                                );
                                            }}
                                        >
                                            <Check className="size-3.5 shrink-0" />
                                            {issue.suggestion}
                                        </Button>
                                    ) : (
                                        <p className="rounded-md bg-muted/50 px-2 py-1 text-xs text-foreground">
                                            {t('editor.proofread.suggestion')}:{' '}
                                            {issue.suggestion}
                                        </p>
                                    ))}
                                {!isLocated && (
                                    <p className="text-[0.7rem] text-muted-foreground/80">
                                        {t('editor.proofread.not_located')}
                                    </p>
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}
        </div>
    );
}
