import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarFooter } from '@/components/app-sidebar-footer';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { ArticleEditorChromeProvider } from '@/contexts/article-editor-chrome-context';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <ArticleEditorChromeProvider>
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent
                    variant="sidebar"
                    className="flex h-full min-h-0 flex-1 flex-col overflow-hidden md:max-h-[calc(100svh-(--spacing(4)))]"
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <div className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain">
                        {children}
                    </div>
                    <AppSidebarFooter />
                </AppContent>
            </AppShell>
        </ArticleEditorChromeProvider>
    );
}
