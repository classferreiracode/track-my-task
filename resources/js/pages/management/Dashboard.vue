<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CheckSquare, Clock, CreditCard, LayoutGrid, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ManagementLayout from '@/layouts/ManagementLayout.vue';


type Props = {
    kpi: {
        total_users: number;
        total_workspaces: number;
        active_subscriptions: number;
        mrr: number;
        total_tracked_seconds: number;
        total_tasks: number;
    };
    plans: {
        counts: Record<string, number>;
        prices: Record<string, number>;
    };
    users: Array<{
        id: number;
        name: string;
        email: string;
        workspaces_count: number;
        created_at: string | null;
    }>;
    subscriptions: Array<{
        id: number;
        workspace: {
            id: number | null;
            name: string | null;
        };
        plan_key: string;
        status: string;
        started_at: string | null;
    }>;
};

const props = defineProps<Props>();

const planKeys = computed(() => Object.keys(props.plans.counts ?? {}));
const maxPlanCount = computed(() =>
    Math.max(1, ...Object.values(props.plans.counts ?? {})),
);
const formatCurrency = (value: number) =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        maximumFractionDigits: 0,
    }).format(value);

const formatHours = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours === 0) {
        return `${minutes}m`;
    }

    return `${hours}h ${minutes}m`;
};
</script>

<template>
    <ManagementLayout title="Dashboard">
        <Head title="Management Dashboard" />

        <div class="space-y-8">
            <Heading
                title="Dashboard de gestao"
                description="Resumo de usuarios, planos ativos e receita estimada."
            />

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Usuarios</CardTitle>
                            <CardDescription>Total cadastrados</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <Users class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ props.kpi.total_users }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Workspaces</CardTitle>
                            <CardDescription>Total cadastrados</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <LayoutGrid class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ props.kpi.total_workspaces }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Planos ativos</CardTitle>
                            <CardDescription>Workspaces com assinatura</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <CreditCard class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ props.kpi.active_subscriptions }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Total de tasks</CardTitle>
                            <CardDescription>Tarefas cadastradas</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <CheckSquare class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ props.kpi.total_tasks }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Tempo trackeado</CardTitle>
                            <CardDescription>Total do sistema</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <Clock class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ formatHours(props.kpi.total_tracked_seconds) }}
                    </CardContent>
                </Card>
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Receita estimada</CardTitle>
                            <CardDescription>MRR atual</CardDescription>
                        </div>
                        <div class="rounded-full border border-border/70 bg-muted/40 p-2">
                            <Wallet class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ formatCurrency(props.kpi.mrr) }}
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Distribuicao de planos</CardTitle>
                        <CardDescription>Workspaces ativos por plano</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="planKey in planKeys"
                                :key="planKey"
                                class="flex items-center gap-3"
                            >
                                <div class="w-20 text-xs font-medium uppercase text-muted-foreground">
                                    {{ planKey }}
                                </div>
                                <div class="flex-1">
                                    <div class="h-2 rounded-full bg-muted">
                                        <div
                                            class="h-2 rounded-full bg-primary/80"
                                            :style="{
                                                width: `${(props.plans.counts[planKey] / maxPlanCount) * 100}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <span class="text-xs text-muted-foreground">
                                    {{ props.plans.counts[planKey] ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Financeiro basico</CardTitle>
                        <CardDescription>Precos configurados</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div
                            v-for="planKey in planKeys"
                            :key="`price-${planKey}`"
                            class="flex items-center justify-between"
                        >
                            <span class="font-medium capitalize">{{ planKey }}</span>
                            <span class="text-muted-foreground">
                                {{ formatCurrency(props.plans.prices[planKey] ?? 0) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Usuarios recentes</CardTitle>
                        <CardDescription>Ultimos 20 usuarios</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div
                                v-for="user in props.users"
                                :key="user.id"
                                class="rounded-lg border border-border/70 bg-card/70 p-3"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                    <Badge variant="outline">
                                        {{ user.workspaces_count }} workspaces
                                    </Badge>
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    Criado em {{ user.created_at ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Planos ativos</CardTitle>
                        <CardDescription>Ultimas assinaturas</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div
                                v-for="subscription in props.subscriptions"
                                :key="subscription.id"
                                class="rounded-lg border border-border/70 bg-card/70 p-3"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">
                                            {{ subscription.workspace.name ?? 'Workspace' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ subscription.started_at ?? '—' }}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {{ subscription.plan_key }}
                                    </Badge>
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    Status: {{ subscription.status }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </ManagementLayout>
</template>
