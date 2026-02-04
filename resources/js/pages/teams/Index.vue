<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import WorkspaceInvitationController from '@/actions/App/Http/Controllers/WorkspaceInvitationController';
import WorkspaceMemberController from '@/actions/App/Http/Controllers/WorkspaceMemberController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PlanLimitBanner from '@/components/PlanLimitBanner.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as teamsIndex } from '@/routes/teams';
import { type BreadcrumbItem } from '@/types';

type Workspace = {
    id: number;
    name: string;
    slug: string;
    role?: string | null;
};

type Member = {
    id: number;
    name: string;
    email: string;
    role?: string | null;
    weekly_capacity_minutes?: number | null;
    is_active?: boolean | null;
};

type Invitation = {
    id: number;
    email: string;
    role: string;
    token: string;
    accepted_at?: string | null;
    expires_at?: string | null;
};

type PlanPayload = {
    plan: {
        key: string;
        name: string;
        description?: string | null;
    };
    limits: Record<string, number | null>;
    usage: {
        members_count: number;
    };
    upgrade_url: string;
};

type Props = {
    workspaces: Workspace[];
    selectedWorkspaceId: number | null;
    members: Member[];
    invitations: Invitation[];
    plan: PlanPayload | null;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Equipe',
        href: teamsIndex().url,
    },
];

const page = usePage();
const { toast, confirm } = useAlerts();

const currentUserId = computed(() => {
    const auth = page.props.auth as { user?: { id: number } } | undefined;
    return auth?.user?.id ?? null;
});

const inviteErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        email: errors?.email,
        role: errors?.role,
    };
});

const memberErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        role: errors?.role,
        weekly_capacity_minutes: errors?.weekly_capacity_minutes,
    };
});

const workspaceErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        workspace: errors?.workspace,
    };
});

const flashSuccess = computed(() => {
    const flash = page.props.flash as { success?: string } | undefined;
    return flash?.success;
});

watch(
    flashSuccess,
    (message) => {
        if (!message) {
            return;
        }

        void toast({
            icon: 'success',
            title: message,
        });
    },
    { immediate: true },
);

const workspaces = computed(() =>
    Array.isArray(props.workspaces) ? props.workspaces : [],
);

const selectedWorkspace = computed(
    () =>
        workspaces.value.find(
            (workspace) => workspace.id === props.selectedWorkspaceId,
        ) ?? workspaces.value[0] ?? null,
);

const safeWorkspaceId = computed(() => selectedWorkspace.value?.id ?? 0);
const selectedWorkspaceRole = computed(() => selectedWorkspace.value?.role ?? null);
const canManageMembers = computed(() =>
    ['owner', 'admin'].includes(selectedWorkspaceRole.value ?? ''),
);
const canLeaveWorkspace = computed(
    () => selectedWorkspaceRole.value !== 'owner' && safeWorkspaceId.value > 0,
);

const plan = computed(() => props.plan);
const memberLimitReached = computed(() => {
    const limit = plan.value?.limits?.max_members;
    const current = plan.value?.usage?.members_count;

    if (limit === null || limit === undefined || current === undefined) {
        return false;
    }

    return current >= limit;
});

const members = computed(() =>
    Array.isArray(props.members)
        ? props.members.filter(
              (member) =>
                  member &&
                  member.id !== null &&
                  member.id !== undefined &&
                  Number.isFinite(Number(member.id)),
          )
        : [],
);

const roleOptions = [
    { value: 'owner', label: 'Owner' },
    { value: 'admin', label: 'Admin' },
    { value: 'editor', label: 'Editor' },
    { value: 'member', label: 'Member' },
    { value: 'viewer', label: 'Viewer' },
];

const formatCapacity = (minutes?: number | null) => {
    if (!minutes) {
        return '—';
    }

    const hours = Math.round((minutes / 60) * 10) / 10;
    return `${hours}h/semana`;
};

const setWorkspace = (workspaceId: number) => {
    router.get(
        teamsIndex().url,
        { workspace: workspaceId },
        { preserveScroll: true },
    );
};

const confirmRemoveMember = async (member: Member) => {
    if (!safeWorkspaceId.value) {
        return;
    }

    const result = await confirm({
        title: 'Remover membro?',
        text: `Remover ${member.name} do workspace?`,
        confirmButtonText: 'Remover',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(
        WorkspaceMemberController.destroy.url({
            workspace: safeWorkspaceId.value,
            user: member.id,
        }),
        { preserveScroll: true },
    );
};

const confirmLeaveWorkspace = async () => {
    if (!safeWorkspaceId.value) {
        return;
    }

    const result = await confirm({
        title: 'Sair do workspace?',
        text: 'Você perderá acesso a este workspace.',
        confirmButtonText: 'Sair',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(
        WorkspaceMemberController.leave.url({
            workspace: safeWorkspaceId.value,
        }),
        {
            onSuccess: () => {
                void toast({
                    icon: 'success',
                    title: 'Você saiu do workspace.',
                });
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Equipe" />

        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-1">
                <Heading
                    title="Equipe e capacidade"
                    description="Gerencie membros, permissões e convites do workspace."
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Membros</CardTitle>
                        <CardDescription>
                            Ajuste permissões e disponibilidade da equipe.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div
                            v-if="!props.members.length"
                            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                        >
                            Nenhum membro neste workspace.
                        </div>
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="rounded-lg border border-border/70 bg-card/70 p-4"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold">
                                        {{ member.name }}
                                        <span
                                            v-if="member.id === currentUserId"
                                            class="text-xs font-normal text-muted-foreground"
                                        >
                                            (você)
                                        </span>
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span>
                                        {{ formatCapacity(member.weekly_capacity_minutes) }}
                                    </span>
                                    <Button
                                        v-if="
                                            canManageMembers &&
                                            member.id !== currentUserId &&
                                            member.role !== 'owner'
                                        "
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="confirmRemoveMember(member)"
                                    >
                                        Remover
                                    </Button>
                                </div>
                            </div>

                            <Form
                                v-if="canManageMembers && safeWorkspaceId && member.id !== null"
                                v-bind="
                                    WorkspaceMemberController.update.form({
                                        workspace: safeWorkspaceId,
                                        user: member.id,
                                    })
                                "
                                class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto_auto]"
                                v-slot="{ processing }"
                            >
                                <select
                                    name="role"
                                    class="border-input flex h-9 w-full rounded-md border bg-card px-3 py-1 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="role in roleOptions"
                                        :key="role.value"
                                        :value="role.value"
                                        :selected="role.value === member.role"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <Input
                                    name="weekly_capacity_minutes"
                                    type="number"
                                    min="0"
                                    placeholder="Capacidade (min/semana)"
                                    class="h-9"
                                    :value="member.weekly_capacity_minutes ?? ''"
                                />
                                <Button type="submit" :disabled="processing">
                                    Salvar
                                </Button>
                                <InputError :message="memberErrors.role" />
                                <InputError :message="memberErrors.weekly_capacity_minutes" />
                            </Form>
                        </div>
                    </CardContent>
                </Card>

                <aside class="flex flex-col gap-6">
                    <Card class="border-border/70 bg-card/80 shadow-sm">
                        <CardHeader>
                            <CardTitle>Workspace ativo</CardTitle>
                            <CardDescription>
                                Selecione o workspace para gerenciar.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-3">
                            <select
                                class="border-input flex h-9 w-full rounded-md border bg-card px-3 py-1 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                :value="selectedWorkspace?.id ?? ''"
                                @change="setWorkspace(Number(($event.target as HTMLSelectElement).value))"
                            >
                                <option
                                    v-for="workspace in props.workspaces"
                                    :key="workspace.id"
                                    :value="workspace.id"
                                >
                                    {{ workspace.name }}
                                </option>
                            </select>
                            <InputError :message="workspaceErrors.workspace" />
                        </CardContent>
                    </Card>

                    <Card class="border-border/70 bg-card/80 shadow-sm">
                        <CardHeader>
                            <CardTitle>Convidar membro</CardTitle>
                            <CardDescription>
                                Envie um convite por e-mail.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <PlanLimitBanner
                                v-if="memberLimitReached"
                                class="mb-4"
                                title="Limite de membros atingido"
                                description="Este workspace alcançou o máximo de membros do plano."
                                :cta-url="plan?.upgrade_url ?? '/settings/plan'"
                            />
                            <Form
                                v-if="safeWorkspaceId"
                                v-bind="
                                    WorkspaceInvitationController.store.form({
                                        workspace: safeWorkspaceId,
                                    })
                                "
                                class="grid gap-3"
                                v-slot="{ processing, recentlySuccessful }"
                            >
                                <Input
                                    name="email"
                                    type="email"
                                    placeholder="email@empresa.com"
                                    required
                                />
                                <InputError :message="inviteErrors.email" />
                                <select
                                    name="role"
                                    class="border-input flex h-9 w-full rounded-md border bg-card px-3 py-1 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="role in roleOptions"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <InputError :message="inviteErrors.role" />
                                <Button
                                    type="submit"
                                    :disabled="processing || memberLimitReached"
                                >
                                    Enviar convite
                                </Button>
                                <span
                                    v-if="recentlySuccessful"
                                    class="text-xs text-muted-foreground"
                                >
                                    Convite enviado.
                                </span>
                            </Form>
                            <div
                                v-else
                                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                            >
                                Selecione um workspace para enviar convites.
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        v-if="canLeaveWorkspace"
                        class="border-border/70 bg-card/80 shadow-sm"
                    >
                        <CardHeader>
                            <CardTitle>Sua participação</CardTitle>
                            <CardDescription>
                                Você pode sair deste workspace quando quiser.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                type="button"
                                variant="destructive"
                                @click="confirmLeaveWorkspace"
                            >
                                Sair do workspace
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="border-border/70 bg-card/80 shadow-sm">
                        <CardHeader>
                            <CardTitle>Convites pendentes</CardTitle>
                            <CardDescription>
                                Compartilhe o token com o convidado.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-3">
                            <div
                                v-if="!props.invitations.length"
                                class="text-sm text-muted-foreground"
                            >
                                Nenhum convite pendente.
                            </div>
                            <div
                                v-for="invite in props.invitations"
                                :key="invite.id"
                                class="rounded-lg border border-border/70 bg-card/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ invite.email }}</span>
                                    <span class="text-muted-foreground">{{ invite.role }}</span>
                                </div>
                                <div class="mt-2 break-all text-[11px] text-muted-foreground">
                                    /invitations/{{ invite.token }}/accept
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
