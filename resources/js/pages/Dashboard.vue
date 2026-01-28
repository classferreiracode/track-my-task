<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    CalendarCheck,
    Clock,
    ListChecks,
} from 'lucide-vue-next';
import { computed } from 'vue';
import TaskBoardController from '@/actions/App/Http/Controllers/TaskBoardController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as tasksIndex } from '@/routes/tasks';
import { type BreadcrumbItem } from '@/types';

type Props = {
    kpi: {
        total_tasks: number;
        completed_tasks: number;
        active_timers: number;
        active_task_names: string[];
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
const page = usePage();

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

const boardErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        name: errors?.name,
    };
});

const chartItems = computed(() => [
    { label: 'Hoje', value: props.kpi.seconds_today },
    { label: 'Semana', value: props.kpi.seconds_week },
    { label: 'Mês', value: props.kpi.seconds_month },
]);

const chartMax = computed(() =>
    Math.max(1, ...chartItems.value.map((item) => item.value)),
);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Visão executiva
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            KPIs atualizados em {{ asOfLabel }}.
                        </p>
                    </div>
                    <Button as-child>
                        <Link :href="tasksIndex()">Abrir board</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardDescription>Tarefas totais</CardDescription>
                            <ListChecks class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl font-semibold">
                            {{ kpi.total_tasks }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ kpi.completed_tasks }} concluídas
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardDescription>Timers ativos</CardDescription>
                            <Activity class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl font-semibold">
                            {{ kpi.active_timers }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-2 text-xs">
                        <Badge variant="secondary" class="w-fit">
                            {{ kpi.active_timers > 0 ? 'Em andamento' : 'Livre' }}
                        </Badge>
                        <p
                            v-if="kpi.active_task_names.length > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ kpi.active_task_names.join(', ') }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardDescription>Horas hoje</CardDescription>
                            <Clock class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl font-semibold">
                            {{ formatSeconds(kpi.seconds_today) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Desde {{ kpi.day_start }}
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardDescription>Horas esta semana</CardDescription>
                            <CalendarCheck class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl font-semibold">
                            {{ formatSeconds(kpi.seconds_week) }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Desde {{ kpi.week_start }}
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.1fr_1fr]">
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Novo board</CardTitle>
                        <CardDescription>
                            Crie projetos para organizar o trabalho do time.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="TaskBoardController.store.form()"
                            class="flex flex-col gap-4"
                            v-slot="{ processing, recentlySuccessful }"
                        >
                            <div class="grid gap-2">
                                <Input
                                    name="name"
                                    placeholder="Nome do board"
                                    required
                                />
                                <InputError :message="boardErrors.name" />
                            </div>
                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="processing">
                                    Criar board
                                </Button>
                                <span
                                    v-if="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Criado!
                                </span>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Acoes rapidas</CardTitle>
                        <CardDescription>
                            Acesse o board para detalhar tarefas e horas.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-3 text-sm text-muted-foreground">
                            <p>
                                Use o menu lateral para alternar entre boards e manter a
                                equipe alinhada.
                            </p>
                            <Button as-child variant="outline">
                                <Link :href="tasksIndex()">Abrir board</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-border/70 bg-card/80 shadow-sm">
                <CardHeader>
                    <CardTitle>Resumo mensal</CardTitle>
                    <CardDescription>
                        Total acumulado no mês corrente.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1 rounded-lg border border-border/70 bg-muted/30 p-4">
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

            <Card class="border-border/70 bg-card/80 shadow-sm">
                <CardHeader>
                    <CardTitle>Volume de horas</CardTitle>
                    <CardDescription>
                        Comparativo entre hoje, semana e mês.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="item in chartItems"
                            :key="item.label"
                            class="grid grid-cols-[80px_1fr_auto] items-center gap-3"
                        >
                            <span class="text-xs font-medium text-muted-foreground">
                                {{ item.label }}
                            </span>
                            <div class="h-2.5 rounded-full bg-muted/50">
                                <div
                                    class="h-2.5 rounded-full bg-primary/80"
                                    :style="{
                                        width: `${Math.round((item.value / chartMax) * 100)}%`,
                                    }"
                                ></div>
                            </div>
                            <span class="text-xs font-medium">
                                {{ formatSeconds(item.value) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
