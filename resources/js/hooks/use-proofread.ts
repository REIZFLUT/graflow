import { useHttp } from '@inertiajs/react';
import type { Editor } from '@tiptap/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { check } from '@/actions/App/Http/Controllers/ProofreadController';
import { useTranslation } from '@/hooks/use-translation';
import { extractPlainTextWithMap, mapIssuesToPositions } from '@/lib/tiptap';
import type { ProofreadIssue } from '@/lib/tiptap';

type ProofreadResponse = {
    issues: ProofreadIssue[];
};

type ProofreadForm = {
    text: string;
    language: string;
};

export type ProofreadErrorReason =
    | 'not_configured'
    | 'unavailable'
    | 'failed'
    | 'generic';

export type UseProofreadReturn = {
    isChecking: boolean;
    hasRun: boolean;
    error: ProofreadErrorReason | null;
    runCheck: (editor: Editor) => Promise<boolean>;
    clearResults: (editor: Editor) => void;
};

const ERROR_TRANSLATION_KEYS: Record<ProofreadErrorReason, string> = {
    not_configured: 'editor.proofread.error_not_configured',
    unavailable: 'editor.proofread.error_unavailable',
    failed: 'editor.proofread.error',
    generic: 'editor.proofread.error',
};

function resolveErrorReason(caught: unknown): ProofreadErrorReason {
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
        return 'not_configured';
    }

    return 'generic';
}

export function useProofread(): UseProofreadReturn {
    const { t } = useTranslation();
    const payloadRef = useRef<ProofreadForm>({
        text: '',
        language: 'de',
    });
    const { submit, transform, processing } = useHttp<
        ProofreadForm,
        ProofreadResponse
    >({
        text: '',
        language: 'de',
    });
    const [hasRun, setHasRun] = useState(false);
    const [error, setError] = useState<ProofreadErrorReason | null>(null);

    useEffect(() => {
        transform(() => payloadRef.current);
    }, [transform]);

    const clearResults = useCallback((editor: Editor) => {
        editor.commands.clearProofread();
        setHasRun(false);
        setError(null);
    }, []);

    const runCheck = useCallback(
        async (editor: Editor): Promise<boolean> => {
            const { text } = extractPlainTextWithMap(editor.state.doc, {
                math: 'latex',
            });

            if (text.trim() === '') {
                editor.commands.clearProofread();
                setHasRun(true);
                toast.message(t('editor.proofread.empty_document'));

                return true;
            }

            payloadRef.current = {
                text,
                language: 'de',
            };

            try {
                const response = await submit(check());
                const issues = mapIssuesToPositions(
                    editor.state.doc,
                    response.issues ?? [],
                );

                editor.commands.setProofreadIssues(issues);
                setError(null);
                setHasRun(true);

                if (issues.length === 0) {
                    toast.success(t('editor.proofread.no_issues'));
                } else {
                    toast.message(
                        t('editor.proofread.issues_found', {
                            count: String(issues.length),
                        }),
                    );
                }

                return true;
            } catch (caught) {
                const reason = resolveErrorReason(caught);

                editor.commands.clearProofread();
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
