import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState
    
} from 'react';
import type {ReactNode} from 'react';

type ArticleEditorChromeState = {
    actions: ReactNode | null;
    toolbar: ReactNode | null;
    statusBar: ReactNode | null;
};

type ArticleEditorChromeContextValue = {
    chrome: ArticleEditorChromeState;
    setChrome: (partial: Partial<ArticleEditorChromeState>) => void;
    clearChrome: () => void;
};

const emptyChrome: ArticleEditorChromeState = {
    actions: null,
    toolbar: null,
    statusBar: null,
};

const ArticleEditorChromeContext =
    createContext<ArticleEditorChromeContextValue | null>(null);

export function ArticleEditorChromeProvider({
    children,
}: {
    children: ReactNode;
}) {
    const [chrome, setChromeState] =
        useState<ArticleEditorChromeState>(emptyChrome);

    const setChrome = useCallback(
        (partial: Partial<ArticleEditorChromeState>) => {
            setChromeState((current) => ({ ...current, ...partial }));
        },
        [],
    );

    const clearChrome = useCallback(() => {
        setChromeState(emptyChrome);
    }, []);

    const value = useMemo(
        () => ({ chrome, setChrome, clearChrome }),
        [chrome, setChrome, clearChrome],
    );

    return (
        <ArticleEditorChromeContext.Provider value={value}>
            {children}
        </ArticleEditorChromeContext.Provider>
    );
}

export function useArticleEditorChrome(): ArticleEditorChromeContextValue {
    const context = useContext(ArticleEditorChromeContext);

    if (!context) {
        throw new Error(
            'useArticleEditorChrome must be used within ArticleEditorChromeProvider',
        );
    }

    return context;
}

export function useOptionalArticleEditorChrome(): ArticleEditorChromeContextValue | null {
    return useContext(ArticleEditorChromeContext);
}
