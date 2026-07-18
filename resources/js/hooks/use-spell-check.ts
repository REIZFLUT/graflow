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

export type UseSpellCheckReturn = {
    isChecking: boolean;
    hasRun: boolean;
    runCheck: (editor: Editor) => Promise<boolean>;
    clearResults: (editor: Editor) => void;
};

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

    useEffect(() => {
        transform(() => payloadRef.current);
    }, [transform]);

    const clearResults = useCallback((editor: Editor) => {
        editor.commands.clearSpellCheck();
        setHasRun(false);
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
            } catch {
                toast.error(t('editor.spellcheck.error'));

                return false;
            }
        },
        [submit, t],
    );

    return {
        isChecking: processing,
        hasRun,
        runCheck,
        clearResults,
    };
}
