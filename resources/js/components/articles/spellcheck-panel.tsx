import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import { AlertTriangle, Check, SpellCheck2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { SpellCheckErrorReason } from '@/hooks/use-spell-check';
import { useTranslation } from '@/hooks/use-translation';
import {
    focusSpellCheckMatch,
    getSpellCheckMatches,
} from '@/lib/tiptap';
import type { MappedSpellCheckMatch } from '@/lib/tiptap';
import { cn } from '@/lib/utils';

type SpellCheckPanelProps = {
    editor: Editor | null;
    hasRun: boolean;
    isChecking?: boolean;
    error?: SpellCheckErrorReason | null;
    focusedMatchId?: string | null;
    onFocusMatch?: (match: MappedSpellCheckMatch) => void;
    onStartCheck?: () => void;
    className?: string;
};

const ERROR_TRANSLATION_KEYS: Record<SpellCheckErrorReason, string> = {
    not_configured: 'editor.spellcheck.error_not_configured',
    unavailable: 'editor.spellcheck.error_unavailable',
    failed: 'editor.spellcheck.error',
    generic: 'editor.spellcheck.error',
};

export default function SpellCheckPanel({
    editor,
    hasRun,
    isChecking = false,
    error = null,
    focusedMatchId = null,
    onFocusMatch,
    onStartCheck,
    className,
}: SpellCheckPanelProps) {
    const { t } = useTranslation();
    const matches =
        useEditorState({
            editor,
            selector: ({ editor: currentEditor }) =>
                getSpellCheckMatches(currentEditor),
        }) ?? [];

    if (!editor) {
        return null;
    }

    const handleFocus = (match: MappedSpellCheckMatch) => {
        focusSpellCheckMatch(editor, match);
        onFocusMatch?.(match);
    };

    return (
        <div className={cn('space-y-4', className)}>
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
                        <SpellCheck2 className="size-4" />
                    )}
                    {isChecking
                        ? t('editor.spellcheck.checking')
                        : t('editor.spellcheck.start')}
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
                    {t('editor.spellcheck.not_run')}
                </p>
            )}

            {!isChecking && !error && hasRun && matches.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    {t('editor.spellcheck.empty')}
                </p>
            )}

            {!isChecking && !error && matches.length > 0 && (
                <ol className="space-y-4">
                    {matches.map((match, index) => {
                        const isFocused = match.id === focusedMatchId;

                        return (
                            <li
                                key={match.id}
                                data-spellcheck-item={match.id}
                                className={cn(
                                    'space-y-2 rounded-md border-b border-border/50 pb-4 transition-colors last:border-b-0 last:pb-0',
                                    isFocused &&
                                        'border-border bg-muted/50 px-3 py-3 ring-1 ring-border/60',
                                )}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <button
                                        type="button"
                                        onClick={() => handleFocus(match)}
                                        className={cn(
                                            'min-w-0 text-left text-xs font-medium text-muted-foreground transition-colors hover:text-foreground',
                                            isFocused && 'text-foreground',
                                        )}
                                    >
                                        {index + 1}. {match.shortMessage}
                                    </button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                                        onClick={() => {
                                            editor.commands.dismissSpellCheckMatch(
                                                match.id,
                                            );
                                        }}
                                    >
                                        <X className="size-3.5" />
                                        {t('editor.spellcheck.dismiss')}
                                    </Button>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => handleFocus(match)}
                                    className="w-full text-left text-sm leading-relaxed text-foreground transition-colors hover:text-foreground/80"
                                >
                                    {match.message}
                                </button>
                                {match.context.length > 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        „{match.context}“
                                    </p>
                                )}
                                {match.replacements.length > 0 ? (
                                    <div className="flex flex-wrap gap-1.5">
                                        {match.replacements.map(
                                            (replacement) => (
                                                <Button
                                                    key={`${match.id}-${replacement}`}
                                                    type="button"
                                                    variant="secondary"
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                    onClick={() => {
                                                        handleFocus(match);
                                                        editor.commands.applySpellCheckReplacement(
                                                            match.id,
                                                            replacement,
                                                        );
                                                    }}
                                                >
                                                    <Check className="size-3.5" />
                                                    {replacement}
                                                </Button>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">
                                        {t('editor.spellcheck.no_suggestions')}
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
