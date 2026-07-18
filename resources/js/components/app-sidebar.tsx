import { Link, usePage } from '@inertiajs/react';
import { FileText, LayoutGrid, Newspaper, SlidersHorizontal } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as editorSettingsSetsIndex } from '@/routes/editor-settings-sets';
import { index as publicationsIndex } from '@/routes/publications';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useTranslation();
    const { can } = usePage().props;

    const mainNavItems: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.articles'),
            href: articlesIndex(),
            icon: FileText,
        },
        {
            title: t('nav.publications'),
            href: publicationsIndex(),
            icon: Newspaper,
        },
        ...(can.manageEditorSettingsSets
            ? [
                  {
                      title: t('nav.editor_settings'),
                      href: editorSettingsSetsIndex(),
                      icon: SlidersHorizontal,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
