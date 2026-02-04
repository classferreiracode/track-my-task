<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import ManagementLayout from '@/layouts/ManagementLayout.vue';

type Workspace = {
    id: number;
    name: string;
    slug: string;
    plan_key: string | null;
    boards_count: number;
    boards: Array<{
        id: number;
        name: string;
    }>;
};

type User = {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
    workspaces_count: number;
    active_memberships_count: number;
    is_active: boolean;
    workspaces: Workspace[];
};

type Props = {
    users: User[];
};

const props = defineProps<Props>();

const filter = ref<'all' | 'active' | 'inactive'>('all');
const selectedUser = ref<User | null>(null);
const isModalOpen = ref(false);

const users = computed(() => props.users ?? []);
const activeCount = computed(() => users.value.filter((user) => user.is_active).length);
const inactiveCount = computed(() => users.value.filter((user) => !user.is_active).length);

const filteredUsers = computed(() => {
    if (filter.value === 'active') {
        return users.value.filter((user) => user.is_active);
    }

    if (filter.value === 'inactive') {
        return users.value.filter((user) => !user.is_active);
    }

    return users.value;
});

const openDetails = (user: User) => {
    selectedUser.value = user;
    isModalOpen.value = true;
};

const planSummary = (user: User) => {
    const plans = user.workspaces
        .map((workspace) => workspace.plan_key ?? 'free')
        .filter(Boolean);
    const unique = Array.from(new Set(plans));
    return unique.length ? unique.join(', ') : 'free';
};
</script>

<template>
    <ManagementLayout title="Usuarios">
        <Head title="Gestao de Usuarios" />

        <div class="space-y-6">
            <Heading
                title="Usuarios do sistema"
                description="Lista completa de usuarios do app com planos e workspaces."
            />

            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Total</CardTitle>
                        <CardDescription>Usuarios cadastrados</CardDescription>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ users.length }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Ativos</CardTitle>
                        <CardDescription>Com membership ativa</CardDescription>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ activeCount }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Inativos</CardTitle>
                        <CardDescription>Sem memberships ativas</CardDescription>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ inactiveCount }}
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <Button
                    size="sm"
                    :variant="filter === 'all' ? 'default' : 'outline'"
                    @click="filter = 'all'"
                >
                    Todos
                </Button>
                <Button
                    size="sm"
                    :variant="filter === 'active' ? 'default' : 'outline'"
                    @click="filter = 'active'"
                >
                    Ativos
                </Button>
                <Button
                    size="sm"
                    :variant="filter === 'inactive' ? 'default' : 'outline'"
                    @click="filter = 'inactive'"
                >
                    Inativos
                </Button>
            </div>

            <Card class="border-border/70 bg-card/80 shadow-sm">
                <CardHeader>
                    <CardTitle>Usuarios</CardTitle>
                    <CardDescription>Detalhes e planos</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 text-sm">
                        <div
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="rounded-lg border border-border/70 bg-card/70 p-3"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Criado em {{ user.created_at ?? '—' }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <Badge variant="outline">
                                        {{ user.workspaces_count }} workspaces
                                    </Badge>
                                    <Badge variant="secondary">
                                        {{ planSummary(user) }}
                                    </Badge>
                                    <Badge :variant="user.is_active ? 'default' : 'destructive'">
                                        {{ user.is_active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openDetails(user)"
                                    >
                                        Detalhes
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="!filteredUsers.length"
                            class="rounded-lg border border-dashed border-border/70 p-6 text-center text-sm text-muted-foreground"
                        >
                            Nenhum usuario encontrado.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ManagementLayout>

    <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Detalhes do usuario</DialogTitle>
                <DialogDescription>
                    Workspaces e boards associados ao usuario.
                </DialogDescription>
            </DialogHeader>
            <div v-if="selectedUser" class="grid gap-4 text-sm">
                <div>
                    <p class="font-medium">{{ selectedUser.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ selectedUser.email }}</p>
                </div>
                <div class="grid gap-3">
                    <div
                        v-for="workspace in selectedUser.workspaces"
                        :key="workspace.id"
                        class="rounded-lg border border-border/70 bg-card/70 p-3"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ workspace.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Plano: {{ workspace.plan_key ?? 'free' }}
                                </p>
                            </div>
                            <Badge variant="outline">
                                {{ workspace.boards_count }} boards
                            </Badge>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                            <span
                                v-for="board in workspace.boards"
                                :key="board.id"
                                class="rounded-full border border-border/70 px-2 py-0.5"
                            >
                                {{ board.name }}
                            </span>
                            <span
                                v-if="!workspace.boards.length"
                                class="text-xs text-muted-foreground"
                            >
                                Nenhum board associado.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
