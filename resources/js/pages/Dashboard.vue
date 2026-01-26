<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as tasksIndex } from '@/routes/tasks';
import { type BreadcrumbItem } from '@/types';

type Props = {
    kpi: {
        total_tasks: number;
        completed_tasks: number;
        active_timers: number;
        seconds_today: number;
        seconds_week: number;
        seconds_month: number;
        day_start: string;
        week_start: string;
        month_start: string;
        as_of: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const asOfLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.kpi.as_of)),
);

const formatSeconds = (seconds: number) => {
    const total = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const parts = [];

    if (hours > 0) {
        parts.push(`${hours}h`);
    }

    parts.push(`${minutes}m`);

    return parts.join(' ');
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Dashboard overview
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            KPIs atualizados em {{ asOfLabel }}.
                        </p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="tasksIndex()">Ver tarefas</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Tarefas totais</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ kpi.total_tasks }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ kpi.completed_tasks }} concluídas
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Timers ativos</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ kpi.active_timers }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex items-center gap-2 text-xs">
                        <Badge variant="secondary">
                            {{ kpi.active_timers > 0 ? 'Em andamento' : 'Livre' }}
                        </Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Horas hoje</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ formatSeconds(kpi.seconds_today) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Desde {{ kpi.day_start }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Horas esta semana</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ formatSeconds(kpi.seconds_week) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Desde {{ kpi.week_start }}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Resumo mensal</CardTitle>
                    <CardDescription>
                        Total acumulado no mês corrente.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1 rounded-lg border bg-muted/30 p-4">
                            <div class="text-xs uppercase text-muted-foreground">
                                Horas no mês
                            </div>
                            <div class="mt-2 text-3xl font-semibold">
                                {{ formatSeconds(kpi.seconds_month) }}
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Desde {{ kpi.month_start }}
                            </p>
                        </div>
                        <div class="flex-1 text-sm text-muted-foreground">
                            Use o painel de tarefas para detalhar o tempo por
                            projeto e acompanhar o progresso diário.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
