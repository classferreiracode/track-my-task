<script setup lang="ts">
import { Form, Link, router, usePage } from '@inertiajs/vue3';
import { EllipsisVertical, Folder, LayoutGrid, ListChecks, Trash2, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import TaskBoardController from '@/actions/App/Http/Controllers/TaskBoardController';
import WorkspaceController from '@/actions/App/Http/Controllers/WorkspaceController';
import InputError from '@/components/InputError.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import PlanLimitBanner from '@/components/PlanLimitBanner.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

import { useAlerts } from '@/composables/useAlerts';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as teamsIndex } from '@/routes/teams';
import { index as tasksIndex } from '@/routes/tasks';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

type BoardNavItem = {
    id: number;
    name: string;
    user_id?: number | null;
};

type WorkspaceNavItem = {
    id: number;
    name: string;
    role?: string | null;
};

type PlanPayload = {
    limits: Record<string, number | null>;
    usage: {
        boards_count: number;
    };
    upgrade_url: string;
};

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
const { toast, confirm } = useAlerts();

const boards = computed(() => {
    const items = page.props.boards as BoardNavItem[] | undefined;
    return items ?? [];
});

const workspaces = computed(() => {
    const items = page.props.workspaces as WorkspaceNavItem[] | undefined;
    return items ?? [];
});

const currentUserId = computed(() => {
    const auth = page.props.auth as { user?: { id: number } } | undefined;
    return auth?.user?.id ?? null;
});

const selectedWorkspaceId = computed(() => {
    const fromProps = page.props.selectedWorkspaceId as number | null | undefined;

    if (fromProps) {
        return fromProps;
    }

    const url = new URL(page.url, window.location.origin);
    const workspaceId = Number(url.searchParams.get('workspace'));

    return Number.isFinite(workspaceId) && workspaceId > 0 ? workspaceId : null;
});

const activeWorkspaceId = computed(() => {
    return selectedWorkspaceId.value ?? workspaces.value[0]?.id ?? null;
});

const activeWorkspaceRole = computed(() => {
    const workspace = workspaces.value.find(
        (item) => item.id === activeWorkspaceId.value,
    );
    return workspace?.role ?? null;
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

const plan = computed(() => page.props.plan as PlanPayload | undefined);
const boardLimitReached = computed(() => {
    const limit = plan.value?.limits?.max_boards;
    const current = plan.value?.usage?.boards_count;

    if (limit === null || limit === undefined || current === undefined) {
        return false;
    }

    return current >= limit;
});

const boardErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        name: errors?.name,
    };
});

const workspaceErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        name: errors?.name,
    };
});

const canDeleteBoard = (board: BoardNavItem) => {
    if (!currentUserId.value) {
        return false;
    }

    if (board.user_id === currentUserId.value) {
        return true;
    }

    return ['owner', 'admin'].includes(activeWorkspaceRole.value ?? '');
};

const confirmDeleteBoard = async (board: BoardNavItem) => {
    const result = await confirm({
        title: 'Excluir board?',
        text: `Você quer remover "${board.name}"?`,
        confirmButtonText: 'Excluir',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(TaskBoardController.destroy.url({ board: board.id }), {
        preserveScroll: true,
        onSuccess: () => {
            void toast({
                icon: 'success',
                title: 'Board removido.',
            });
        },
    });
};

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
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(teamsIndex())"
                            tooltip="Equipe"
                        >
                            <Link :href="teamsIndex()">
                                <Users />
                                <span>Equipe</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarGroupLabel class="mt-4">Workspaces</SidebarGroupLabel>
                <SidebarMenuSub v-if="workspaces.length">
                    <SidebarMenuSubItem
                        v-for="workspace in workspaces"
                        :key="workspace.id"
                    >
                        <SidebarMenuSubButton
                            as-child
                            size="sm"
                            :is-active="selectedWorkspaceId === workspace.id"
                        >
                            <Link :href="tasksIndex({ query: { workspace: workspace.id } })">
                                <span>{{ workspace.name }}</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
                <p
                    v-else
                    class="px-4 text-xs text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden"
                >
                    Nenhum workspace cadastrado.
                </p>
                <Form
                    v-bind="WorkspaceController.store.form()"
                    class="mt-3 flex flex-col gap-2 px-3 group-data-[collapsible=icon]:hidden"
                    v-slot="{ processing, recentlySuccessful }"
                >
                    <Input
                        name="name"
                        placeholder="Novo workspace"
                        required
                        class="h-8 text-xs"
                    />
                    <InputError :message="workspaceErrors.name" />
                    <div class="flex items-center gap-2">
                    <Button
                        size="sm"
                        type="submit"
                        :disabled="processing || boardLimitReached"
                    >
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
                <SidebarGroupLabel class="mt-4">Boards</SidebarGroupLabel>
                <SidebarMenuSub v-if="boards.length">
                    <SidebarMenuSubItem
                        v-for="board in boards"
                        :key="board.id"
                    >
                        <div class="flex items-center gap-1">
                            <SidebarMenuSubButton
                                as-child
                                size="sm"
                                class="flex-1"
                                :is-active="selectedBoardId === board.id"
                            >
                                <Link
                                    :href="tasksIndex({
                                        query: {
                                            board: board.id,
                                            workspace: activeWorkspaceId ?? undefined,
                                        },
                                    })"
                                >
                                    <span>{{ board.name }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                            <DropdownMenu v-if="canDeleteBoard(board)">
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent focus-visible:ring-ring/50 group-data-[collapsible=icon]:hidden inline-flex size-6 items-center justify-center rounded-md outline-none focus-visible:ring-[3px]"
                                    >
                                        <EllipsisVertical class="size-3.5" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-40">
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @select="confirmDeleteBoard(board)"
                                    >
                                        <Trash2 class="size-4" />
                                        Excluir
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
                <p
                    v-else
                    class="px-4 text-xs text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden"
                >
                    Nenhum board cadastrado.
                </p>
                <div
                    v-if="boardLimitReached"
                    class="mt-3 px-3 text-xs group-data-[collapsible=icon]:hidden"
                >
                    <PlanLimitBanner
                        title="Limite de boards atingido"
                        description="Este workspace alcançou o máximo de boards do plano."
                        :cta-url="plan?.upgrade_url ?? '/settings/plan'"
                    />
                </div>
                <Form
                    v-bind="TaskBoardController.store.form()"
                    class="mt-3 flex flex-col gap-2 px-3 group-data-[collapsible=icon]:hidden"
                    v-slot="{ processing, recentlySuccessful }"
                >
                    <input
                        type="hidden"
                        name="workspace_id"
                        :value="selectedWorkspaceId ?? ''"
                    />
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
