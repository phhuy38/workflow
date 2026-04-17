<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, LayoutTemplate, Settings, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermission } from '@/composables/usePermission';
import { dashboard } from '@/routes';
import { index as systemIndex } from '@/routes/admin/system';
import { index as usersIndex } from '@/routes/admin/users';
import { index as templatesIndex } from '@/routes/process-templates';
import type { NavItem } from '@/types';

const { can } = usePermission();

// Dashboard is visible only to roles that can access it (ADR-019: UI-only guard)
const mainNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [];

    if (can('view_all_instances')) {
        items.push({
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        });
    }

    if (can('manage_templates')) {
        items.push({
            title: 'Templates',
            href: templatesIndex().url,
            icon: LayoutTemplate,
        });
    }

    if (can('manage_users')) {
        items.push({
            title: 'User Management',
            href: usersIndex().url,
            icon: Users,
        });
    }

    if (can('manage_system')) {
        items.push({
            title: 'System Settings',
            href: systemIndex().url,
            icon: Settings,
        });
    }

    // TODO Story 5.1: Executor Inbox — visible when can('complete_assigned_steps')
    // TODO Story 7.2: Beneficiary — visible when can('view_own_instances') and !can('view_all_instances')

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
