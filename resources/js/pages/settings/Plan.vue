<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { plan as editPlan } from '@/routes/settings';
import { type BreadcrumbItem } from '@/types';

type Workspace = {
    id: number;
    name: string;
    slug: string;
    role?: string | null;
};

type PlanLimits = Record<string, number | null>;

type PlanSummary = {
    key: string;
    name: string;
    description?: string | null;
    limits: PlanLimits;
};

type Usage = {
    members_count: number;
    boards_count: number;
    exports_count_month: number;
};

type Props = {
    workspaces: Workspace[];
    selectedWorkspaceId: number | null;
    currentPlan: PlanSummary | null;
    usage: Usage | null;
    limits: PlanLimits | null;
    plans: PlanSummary[];
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Plano e cobrança',
        href: editPlan().url,
    },
];

const selectedWorkspace = computed(
    () =>
        props.workspaces.find(
            (workspace) => workspace.id === props.selectedWorkspaceId,
        ) ?? props.workspaces[0] ?? null,
);

const setWorkspace = (workspaceId: number) => {
    router.get(
        editPlan().url,
        { workspace: workspaceId },
        { preserveScroll: true },
    );
};

const limits = computed(() => props.limits ?? {});
const usage = computed(() => props.usage ?? null);

const limitLabel = (key: string) => {
    const labels: Record<string, string> = {
        max_members: 'Membros',
        max_boards: 'Boards',
        max_tasks_per_board: 'Tarefas por board',
        max_exports_per_month: 'Exportações/mês',
        max_active_timers_per_user: 'Timers ativos por usuário',
    };

    return labels[key] ?? key;
};

const formatLimit = (value: number | null | undefined) =>
    value === null || value === undefined ? 'Ilimitado' : value.toString();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Plano e cobrança" />

        <h1 class="sr-only">Plano e cobrança</h1>

        <SettingsLayout>
            <Heading
                variant="small"
                title="Plano e cobrança"
                description="Acompanhe limites e uso do workspace."
            />

            <Card class="border-border/70 bg-card/80 shadow-sm">
                <CardHeader>
                    <CardTitle>Workspace</CardTitle>
                    <CardDescription>
                        Selecione o workspace para ver o plano aplicado.
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
                </CardContent>
            </Card>

            <Card
                v-if="props.currentPlan"
                class="border-border/70 bg-card/80 shadow-sm"
            >
                <CardHeader>
                    <CardTitle>Plano atual</CardTitle>
                    <CardDescription>
                        {{ props.currentPlan.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ props.currentPlan.name }}</span>
                        <Button size="sm" variant="outline" disabled>
                            Plano ativo
                        </Button>
                    </div>

                    <Separator />

                    <div class="grid gap-3">
                        <div class="text-xs font-semibold uppercase text-muted-foreground">
                            Uso no mês
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <span>Membros</span>
                                <span>
                                    {{ usage?.members_count ?? 0 }} /
                                    {{ formatLimit(limits.max_members) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Boards</span>
                                <span>
                                    {{ usage?.boards_count ?? 0 }} /
                                    {{ formatLimit(limits.max_boards) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Exportações</span>
                                <span>
                                    {{ usage?.exports_count_month ?? 0 }} /
                                    {{ formatLimit(limits.max_exports_per_month) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/70 bg-card/80 shadow-sm">
                <CardHeader>
                    <CardTitle>Comparar planos</CardTitle>
                    <CardDescription>
                        Limites configurados por plano (sem cobrança ativa).
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="rounded-lg border border-dashed border-border/70 bg-muted/30 p-4 text-sm text-muted-foreground">
                        Upgrade e faturamento estarão disponíveis após a integração do gateway.
                    </div>
                    <div
                        v-for="plan in props.plans"
                        :key="plan.key"
                        class="rounded-lg border border-border/70 bg-card/70 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold">{{ plan.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ plan.description }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                :variant="plan.key === props.currentPlan?.key ? 'secondary' : 'outline'"
                                disabled
                            >
                                {{ plan.key === props.currentPlan?.key ? 'Atual' : 'Em breve' }}
                            </Button>
                        </div>
                        <div class="mt-4 grid gap-2 text-xs text-muted-foreground">
                            <div
                                v-for="(value, key) in plan.limits"
                                :key="`${plan.key}-${key}`"
                                class="flex items-center justify-between"
                            >
                                <span>{{ limitLabel(key) }}</span>
                                <span class="text-foreground">
                                    {{ formatLimit(value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </SettingsLayout>
    </AppLayout>
</template>
