import { useHttp } from '@inertiajs/react';
import type { Editor } from '@tiptap/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { check } from '@/actions/App/Http/Controllers/SpellCheckController';
import { useTranslation } from '@/hooks/use-translation';
import {
    extractPlainTextWithMap,
    mapMatchesToPositions,
} from '@/lib/tiptap';
import type { LanguageToolMatch } from '@/lib/tiptap';

type SpellCheckResponse = {
    matches: LanguageToolMatch[];
};

type SpellCheckForm = {
    text: string;
    language: string;
    level: string;
};

export type SpellCheckErrorReason =
    | 'not_configured'
    | 'unavailable'
    | 'failed'
    | 'generic';

export type UseSpellCheckReturn = {
    isChecking: boolean;
    hasRun: boolean;
    error: SpellCheckErrorReason | null;
    runCheck: (editor: Editor) => Promise<boolean>;
    clearResults: (editor: Editor) => void;
};

const ERROR_TRANSLATION_KEYS: Record<SpellCheckErrorReason, string> = {
    not_configured: 'editor.spellcheck.error_not_configured',
    unavailable: 'editor.spellcheck.error_unavailable',
    failed: 'editor.spellcheck.error',
    generic: 'editor.spellcheck.error',
};

function resolveErrorReason(caught: unknown): SpellCheckErrorReason {
    const response = (
        caught as { response?: { status?: number; data?: string } } | null
    )?.response;

    if (!response) {
        return 'generic';
    }

    let reason: string | undefined;

    if (typeof response.data === 'string' && response.data.length > 0) {
        try {
            reason = (JSON.parse(response.data) as { reason?: string }).reason;
        } catch {
            reason = undefined;
        }
    }

    if (
        reason === 'not_configured' ||
        reason === 'unavailable' ||
        reason === 'failed'
    ) {
        return reason;
    }

    if (response.status === 503) {
        return 'unavailable';
    }

    return 'generic';
}

export function useSpellCheck(): UseSpellCheckReturn {
    const { t } = useTranslation();
    const payloadRef = useRef<SpellCheckForm>({
        text: '',
        language: 'de-DE',
        level: 'picky',
    });
    const { submit, transform, processing } = useHttp<
        SpellCheckForm,
        SpellCheckResponse
    >({
        text: '',
        language: 'de-DE',
        level: 'picky',
    });
    const [hasRun, setHasRun] = useState(false);
    const [error, setError] = useState<SpellCheckErrorReason | null>(null);

    useEffect(() => {
        transform(() => payloadRef.current);
    }, [transform]);

    const clearResults = useCallback((editor: Editor) => {
        editor.commands.clearSpellCheck();
        setHasRun(false);
        setError(null);
    }, []);

    const runCheck = useCallback(
        async (editor: Editor): Promise<boolean> => {
            const { text } = extractPlainTextWithMap(editor.state.doc);

            if (text.trim() === '') {
                editor.commands.clearSpellCheck();
                setHasRun(true);
                toast.message(t('editor.spellcheck.empty_document'));

                return true;
            }

            payloadRef.current = {
                text,
                language: 'de-DE',
                level: 'picky',
            };

            try {
                const response = await submit(check());
                const matches = mapMatchesToPositions(
                    editor.state.doc,
                    response.matches ?? [],
                );

                editor.commands.setSpellCheckMatches(matches);
                setError(null);
                setHasRun(true);

                if (matches.length === 0) {
                    toast.success(t('editor.spellcheck.no_issues'));
                } else {
                    toast.message(
                        t('editor.spellcheck.issues_found', {
                            count: String(matches.length),
                        }),
                    );
                }

                return true;
            } catch (caught) {
                const reason = resolveErrorReason(caught);

                editor.commands.clearSpellCheck();
                setError(reason);
                setHasRun(true);
                toast.error(t(ERROR_TRANSLATION_KEYS[reason]));

                return false;
            }
        },
        [submit, t],
    );

    return {
        isChecking: processing,
        hasRun,
        error,
        runCheck,
        clearResults,
    };
}
