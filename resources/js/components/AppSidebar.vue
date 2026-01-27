<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, ListChecks } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as tasksIndex } from '@/routes/tasks';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

type BoardNavItem = {
    id: number;
    name: string;
};

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const boards = computed(() => {
    const items = page.props.boards as BoardNavItem[] | undefined;
    return items ?? [];
});

const selectedBoardId = computed(() => {
    const fromProps = page.props.selectedBoardId as number | null | undefined;

    if (fromProps) {
        return fromProps;
    }

    const url = new URL(page.url, window.location.origin);
    const boardId = Number(url.searchParams.get('board'));

    return Number.isFinite(boardId) && boardId > 0 ? boardId : null;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
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
            <SidebarMenu class="px-2 py-0">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(dashboard())"
                        tooltip="Dashboard"
                    >
                        <Link :href="dashboard()">
                            <LayoutGrid />
                            <span>Dashboard</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(tasksIndex())"
                        tooltip="Tasks"
                    >
                        <Link :href="tasksIndex()">
                            <ListChecks />
                            <span>Tasks</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenuSub v-if="boards.length">
                <SidebarMenuSubItem
                    v-for="board in boards"
                    :key="board.id"
                >
                    <SidebarMenuSubButton
                        as-child
                        size="sm"
                        :is-active="selectedBoardId === board.id"
                    >
                        <Link :href="tasksIndex({ query: { board: board.id } })">
                            <span>{{ board.name }}</span>
                        </Link>
                    </SidebarMenuSubButton>
                </SidebarMenuSubItem>
            </SidebarMenuSub>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
