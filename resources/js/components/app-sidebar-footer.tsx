import { useOptionalArticleEditorChrome } from '@/contexts/article-editor-chrome-context';

export function AppSidebarFooter() {
    const chromeContext = useOptionalArticleEditorChrome();
    const statusBar = chromeContext?.chrome.statusBar;

    if (!statusBar) {
        return null;
    }

    return (
        <footer className="z-20 shrink-0 border-t border-border/40 bg-muted/15">
            <div className="flex h-8 items-center px-6 md:px-4">
                {statusBar}
            </div>
        </footer>
    );
}
