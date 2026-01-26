<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskColumnController from '@/actions/App/Http/Controllers/TaskColumnController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import TaskOrderController from '@/actions/App/Http/Controllers/TaskOrderController';
import TaskTimerController from '@/actions/App/Http/Controllers/TaskTimerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as tasksIndex, report as tasksReport } from '@/routes/tasks';
import { type BreadcrumbItem } from '@/types';

type TaskColumn = {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
};

type Task = {
    id: number;
    title: string;
    description?: string | null;
    column_id: number | null;
    sort_order: number;
    is_completed: boolean;
    completed_at?: string | null;
    active_entry?: {
        id: number;
        started_at: string | null;
    } | null;
    totals: {
        daily: number;
        weekly: number;
        monthly: number;
    };
};

type Props = {
    columns: TaskColumn[];
    tasks: Task[];
    reporting: {
        day_start: string;
        week_start: string;
        month_start: string;
        as_of: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: tasksIndex().url,
    },
];

const page = usePage();

const timerError = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return errors?.timer;
});

const exportErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;
    return {
        start: errors?.start,
        end: errors?.end,
    };
});

const asOfLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.reporting.as_of)),
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

const formatDateTime = (value?: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const formatDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    const localDate = new Date(year, month - 1, day);

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
    }).format(localDate);
};

const formatMonth = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    const localDate = new Date(year, month - 1, day);

    return new Intl.DateTimeFormat('pt-BR', {
        month: 'long',
        year: 'numeric',
    }).format(localDate);
};

const startTimer = (task: Task) => {
    router.post(TaskTimerController.store(task.id).url, {}, {
        preserveScroll: true,
    });
};

const stopTimer = (task: Task) => {
    router.patch(TaskTimerController.update(task.id).url, {}, {
        preserveScroll: true,
    });
};

const deleteTask = (task: Task) => {
    if (!window.confirm('Delete this task?')) {
        return;
    }

    router.delete(TaskController.destroy(task.id).url, {
        preserveScroll: true,
    });
};

const exportStart = ref(props.reporting.month_start);
const exportEnd = ref(props.reporting.as_of.slice(0, 10));

const reportUrl = computed(() => tasksReport().url);

const sortedColumns = computed(() =>
    [...props.columns].sort((left, right) => left.sort_order - right.sort_order),
);

const tasksByColumn = computed(() => {
    const grouped: Record<number, Task[]> = {};

    sortedColumns.value.forEach((column) => {
        grouped[column.id] = [];
    });

    props.tasks.forEach((task) => {
        const fallbackColumnId = sortedColumns.value[0]?.id;
        const columnId = task.column_id ?? fallbackColumnId;

        if (!columnId) {
            return;
        }

        grouped[columnId] ??= [];
        grouped[columnId].push(task);
    });

    Object.values(grouped).forEach((tasks) => {
        tasks.sort((left, right) => left.sort_order - right.sort_order);
    });

    return grouped;
});

const draggedTaskId = ref<number | null>(null);

const onDragStart = (task: Task) => {
    draggedTaskId.value = task.id;
};

const onDragEnd = () => {
    draggedTaskId.value = null;
};

const reorderColumn = (columnId: number, targetTaskId?: number) => {
    if (!draggedTaskId.value) {
        return;
    }

    const tasksInColumn = tasksByColumn.value[columnId] ?? [];
    const orderedIds = tasksInColumn.map((task) => task.id);
    const movedTaskId = draggedTaskId.value;

    const existingIndex = orderedIds.indexOf(movedTaskId);
    if (existingIndex !== -1) {
        orderedIds.splice(existingIndex, 1);
    }

    let insertIndex = orderedIds.length;
    if (targetTaskId) {
        const targetIndex = orderedIds.indexOf(targetTaskId);
        if (targetIndex !== -1) {
            insertIndex = targetIndex;
        }
    }

    orderedIds.splice(insertIndex, 0, movedTaskId);

    router.patch(
        TaskOrderController.update().url,
        {
            column_id: columnId,
            ordered_ids: orderedIds,
        },
        { preserveScroll: true },
    );

    draggedTaskId.value = null;
};

const onDropColumn = (columnId: number) => {
    reorderColumn(columnId);
};

const onDropTask = (columnId: number, taskId: number) => {
    reorderColumn(columnId, taskId);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tasks" />

        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-col gap-1">
                <Heading
                    title="Board de tarefas"
                    description="Arraste os cards entre os status e acompanhe os relatórios."
                />
                <p class="text-xs text-muted-foreground">
                    Relatórios atualizados em {{ asOfLabel }}.
                </p>
            </div>

            <div
                v-if="timerError"
                class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
            >
                {{ timerError }}
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Nova tarefa</CardTitle>
                        <CardDescription>
                            Crie uma tarefa e mova no board.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="TaskController.store.form()"
                            class="flex flex-col gap-4"
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <div class="grid gap-2">
                                <Input
                                    name="title"
                                    placeholder="Título da tarefa"
                                    required
                                    autofocus
                                />
                                <InputError :message="errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <textarea
                                    name="description"
                                    rows="3"
                                    class="min-h-[96px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Descrição opcional"
                                />
                                <InputError :message="errors.description" />
                            </div>
                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="processing">
                                    Adicionar
                                </Button>
                                <span
                                    v-if="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Adicionado!
                                </span>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Exportar relatório</CardTitle>
                        <CardDescription>
                            Baixe um CSV compatível com Excel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="flex flex-col gap-3" method="get" :action="reportUrl">
                            <div class="grid gap-2">
                                <label class="text-xs font-medium">Início</label>
                                <Input
                                    v-model="exportStart"
                                    name="start"
                                    type="date"
                                />
                                <InputError :message="exportErrors.start" />
                            </div>
                            <div class="grid gap-2">
                                <label class="text-xs font-medium">Fim</label>
                                <Input
                                    v-model="exportEnd"
                                    name="end"
                                    type="date"
                                />
                                <InputError :message="exportErrors.end" />
                            </div>
                            <Button type="submit" variant="outline">
                                Exportar Excel
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Novo status</CardTitle>
                        <CardDescription>
                            Crie mais colunas para o seu board.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="TaskColumnController.store.form()"
                            class="flex flex-col gap-4"
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <div class="grid gap-2">
                                <Input
                                    name="name"
                                    placeholder="Nome do status"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="processing">
                                    Adicionar coluna
                                </Button>
                                <span
                                    v-if="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Criada!
                                </span>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Períodos padrão</CardTitle>
                        <CardDescription>
                            Totais a partir de cada período.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Hoje</span>
                                <span class="font-medium">
                                    {{ formatDate(reporting.day_start) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Semana</span>
                                <span class="font-medium">
                                    {{ formatDate(reporting.week_start) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Mês</span>
                                <span class="font-medium">
                                    {{ formatMonth(reporting.month_start) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div
                    v-for="column in sortedColumns"
                    :key="column.id"
                    class="flex min-h-[420px] flex-col gap-3 rounded-xl border bg-muted/10 p-4"
                    @dragover.prevent
                    @drop="onDropColumn(column.id)"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold">
                                {{ column.name }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Arraste cards para este status.
                            </p>
                        </div>
                        <Badge variant="outline">
                            {{ tasksByColumn[column.id]?.length ?? 0 }}
                        </Badge>
                    </div>

                    <div class="flex flex-1 flex-col gap-3">
                        <div
                            v-if="(tasksByColumn[column.id]?.length ?? 0) === 0"
                            class="rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            Solte tarefas aqui.
                        </div>

                        <div
                            v-for="task in tasksByColumn[column.id]"
                            :key="task.id"
                            class="rounded-lg border bg-card p-4 shadow-sm"
                            draggable="true"
                            @dragstart="onDragStart(task)"
                            @dragend="onDragEnd"
                            @dragover.prevent
                            @drop.stop="onDropTask(column.id, task.id)"
                        >
                            <div class="flex flex-col gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold">
                                            {{ task.title }}
                                        </h3>
                                        <Badge
                                            v-if="task.active_entry"
                                            variant="secondary"
                                        >
                                            Rodando
                                        </Badge>
                                    </div>
                                    <p
                                        v-if="task.description"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ task.description }}
                                    </p>
                                    <p
                                        v-if="task.active_entry"
                                        class="mt-2 text-[11px] text-muted-foreground"
                                    >
                                        Iniciado {{
                                            formatDateTime(task.active_entry.started_at)
                                        }}
                                    </p>
                                </div>

                                <div class="grid gap-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Hoje</span>
                                        <span class="font-medium">
                                            {{ formatSeconds(task.totals.daily) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Semana</span>
                                        <span class="font-medium">
                                            {{ formatSeconds(task.totals.weekly) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Mês</span>
                                        <span class="font-medium">
                                            {{ formatSeconds(task.totals.monthly) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-if="!task.active_entry"
                                        size="sm"
                                        variant="secondary"
                                        @click="startTimer(task)"
                                    >
                                        Start
                                    </Button>
                                    <Button
                                        v-else
                                        size="sm"
                                        variant="destructive"
                                        @click="stopTimer(task)"
                                    >
                                        Stop
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="deleteTask(task)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
