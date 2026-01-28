<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

type TaskBoard = {
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
    boards: TaskBoard[];
    selectedBoardId: number | null;
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

const completeTask = (task: Task) => {
    const payload: Record<string, number | boolean> = {
        is_completed: true,
    };

    if (doneColumnId.value) {
        payload.task_column_id = doneColumnId.value;
    }

    router.patch(TaskController.update(task.id).url, payload, {
        preserveScroll: true,
    });
};

const exportStart = ref(props.reporting.month_start);
const exportEnd = ref(props.reporting.as_of.slice(0, 10));

const reportUrl = computed(() => tasksReport().url);

const doneSlugs = ['done', 'concluido', 'concluida', 'concluidos', 'concluidas'];

const sortedBoards = computed(() =>
    [...props.boards].sort((left, right) => left.sort_order - right.sort_order),
);

const activeBoardId = computed(
    () => props.selectedBoardId ?? sortedBoards.value[0]?.id ?? null,
);

const activeBoard = computed(
    () =>
        sortedBoards.value.find((board) => board.id === activeBoardId.value) ??
        null,
);

const sortedColumns = computed(() =>
    [...props.columns].sort((left, right) => left.sort_order - right.sort_order),
);

const doneColumnId = computed(
    () => sortedColumns.value.find((column) => doneSlugs.includes(column.slug))?.id ?? null,
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
const draggedColumnId = ref<number | null>(null);
const dragType = ref<'task' | 'column' | null>(null);

const onDragStart = (task: Task) => {
    draggedTaskId.value = task.id;
    dragType.value = 'task';
};

const onDragEnd = () => {
    draggedTaskId.value = null;
    draggedColumnId.value = null;
    dragType.value = null;
};

const onColumnDragStart = (columnId: number) => {
    draggedColumnId.value = columnId;
    dragType.value = 'column';
};

const reorderColumnTasks = (columnId: number, targetTaskId?: number) => {
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
    if (dragType.value === 'column') {
        reorderColumns(columnId);
        return;
    }

    reorderColumnTasks(columnId);
};

const onDropTask = (columnId: number, taskId: number) => {
    if (dragType.value === 'task') {
        reorderColumnTasks(columnId, taskId);
        return;
    }

    if (dragType.value === 'column') {
        reorderColumns(columnId);
    }
};

const reorderColumns = (targetColumnId: number) => {
    if (!draggedColumnId.value) {
        return;
    }

    if (!activeBoardId.value) {
        return;
    }

    const orderedIds = sortedColumns.value.map((column) => column.id);
    const movedId = draggedColumnId.value;
    const existingIndex = orderedIds.indexOf(movedId);
    if (existingIndex !== -1) {
        orderedIds.splice(existingIndex, 1);
    }

    const targetIndex = orderedIds.indexOf(targetColumnId);
    const insertIndex = targetIndex === -1 ? orderedIds.length : targetIndex;
    orderedIds.splice(insertIndex, 0, movedId);

    router.patch(
        TaskColumnController.order().url,
        {
            task_board_id: activeBoardId.value,
            ordered_ids: orderedIds,
        },
        { preserveScroll: true },
    );

    draggedColumnId.value = null;
    dragType.value = null;
};

const setBoard = (boardId: number) => {
    router.get(
        tasksIndex().url,
        { board: boardId },
        { preserveScroll: true },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tasks" />

        <div class="flex flex-1 flex-col gap-8 p-6">
            <div class="flex flex-col gap-1">
                <Heading
                    title="Board de tarefas"
                    description="Arraste os cards entre status, acompanhe métricas e exporte relatórios."
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
                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Boards</CardTitle>
                        <CardDescription>
                            Troque o projeto ativo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div class="grid gap-2">
                            <label class="text-xs font-medium">
                                Board ativo
                            </label>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="border-input flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 py-1 text-left text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <span class="truncate">
                                            {{ activeBoard?.name ?? 'Padrão' }}
                                        </span>
                                        <ChevronsUpDown class="ml-2 size-4 text-muted-foreground" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56"
                                    align="start"
                                >
                                    <DropdownMenuItem
                                        v-for="board in sortedBoards"
                                        :key="board.id"
                                        @select="setBoard(board.id)"
                                    >
                                        {{ board.name }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/80 shadow-sm">
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
                            <input
                                type="hidden"
                                name="task_board_id"
                                :value="activeBoardId ?? ''"
                            />
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

                <Card class="border-border/70 bg-card/80 shadow-sm">
                    <CardHeader>
                        <CardTitle>Exportar relatório</CardTitle>
                        <CardDescription>
                            Baixe um CSV compatível com Excel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="flex flex-col gap-3" method="get" :action="reportUrl">
                            <input
                                type="hidden"
                                name="task_board_id"
                                :value="activeBoardId ?? ''"
                            />
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

                <Card class="border-border/70 bg-card/80 shadow-sm">
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
                            <input
                                type="hidden"
                                name="task_board_id"
                                :value="activeBoardId ?? ''"
                            />
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

                <Card class="border-border/70 bg-card/80 shadow-sm">
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

            <div class="h-10"></div>

            <div class="flex items-center justify-between gap-3">
                <h2 class="text-2xl font-semibold tracking-tight">
                    Board: {{ activeBoard?.name ?? 'Padrão' }}
                </h2>
                <span class="rounded-full border border-border/70 bg-card/70 px-3 py-1 text-xs text-muted-foreground">
                    {{ sortedColumns.length }} status
                </span>
            </div>
            <hr class="border-t border-border/70" />

            <div
                class="grid gap-4 grid-cols-[repeat(auto-fit,minmax(240px,1fr))]"
            >
                <div
                    v-for="column in sortedColumns"
                    :key="column.id"
                    class="flex min-h-105 flex-col gap-3 rounded-xl border border-border/70 bg-card/70 p-4 shadow-sm"
                    @dragover.prevent
                    @drop="onDropColumn(column.id)"
                >
                    <div
                        class="flex items-center justify-between"
                        draggable="true"
                        @dragstart="onColumnDragStart(column.id)"
                        @dragend="onDragEnd"
                    >
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

                    <div
                        class="flex flex-1 flex-col gap-3 min-h-0"
                        :class="{
                            'max-h-105 overflow-y-auto pr-1 scrollbar-soft':
                                (tasksByColumn[column.id]?.length ?? 0) > 3,
                        }"
                    >
                        <div
                            v-if="(tasksByColumn[column.id]?.length ?? 0) === 0"
                            class="rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            Solte tarefas aqui.
                        </div>

                        <div
                            v-for="task in tasksByColumn[column.id]"
                            :key="task.id"
                            class="rounded-lg border border-border/70 bg-card/90 p-4 shadow-sm"
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
                                    <Button
                                        v-if="!task.is_completed"
                                        size="sm"
                                        variant="outline"
                                        @click="completeTask(task)"
                                    >
                                        Concluir
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
