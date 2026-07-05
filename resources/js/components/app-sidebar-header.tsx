import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useOptionalArticleEditorChrome } from '@/contexts/article-editor-chrome-context';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const chromeContext = useOptionalArticleEditorChrome();
    const chrome = chromeContext?.chrome;

    return (
        <header className="z-20 shrink-0 border-b border-sidebar-border/50 bg-background/95 backdrop-blur-md">
            <div className="flex h-16 items-center justify-between gap-4 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
                <div className="flex min-w-0 items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {chrome?.actions && (
                    <div className="flex shrink-0 items-center gap-2">
                        {chrome.actions}
                    </div>
                )}
            </div>

            {chrome?.toolbar && (
                <div className="border-t border-border/40 bg-muted/15">
                    <div className="flex items-center px-6 py-2 md:px-4">
                        {chrome.toolbar}
                    </div>
                </div>
            )}
        </header>
    );
}
