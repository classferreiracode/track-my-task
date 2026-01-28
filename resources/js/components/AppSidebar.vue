<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import {  Folder, LayoutGrid, ListChecks } from 'lucide-vue-next';
import { computed } from 'vue';
import TaskBoardController from '@/actions/App/Http/Controllers/TaskBoardController';
import InputError from '@/components/InputError.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
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

const boardErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        name: errors?.name,
    };
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/classferreiracode',
        icon: Folder,
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
            <SidebarGroup>
                <SidebarGroupLabel>Menu</SidebarGroupLabel>
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
                <SidebarGroupLabel class="mt-4">Boards</SidebarGroupLabel>
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
                <p
                    v-else
                    class="px-4 text-xs text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden"
                >
                    Nenhum board cadastrado.
                </p>
                <Form
                    v-bind="TaskBoardController.store.form()"
                    class="mt-3 flex flex-col gap-2 px-3 group-data-[collapsible=icon]:hidden"
                    v-slot="{ processing, recentlySuccessful }"
                >
                    <Input
                        name="name"
                        placeholder="Novo board"
                        required
                        class="h-8 text-xs"
                    />
                    <InputError :message="boardErrors.name" />
                    <div class="flex items-center gap-2">
                        <Button size="sm" type="submit" :disabled="processing">
                            Criar
                        </Button>
                        <span
                            v-if="recentlySuccessful"
                            class="text-[11px] text-sidebar-foreground/70"
                        >
                            Criado
                        </span>
                    </div>
                </Form>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
