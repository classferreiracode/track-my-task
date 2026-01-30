<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import Multiselect from 'vue-multiselect';
import TaskColumnController from '@/actions/App/Http/Controllers/TaskColumnController';
import TaskCommentController from '@/actions/App/Http/Controllers/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import TaskLabelController from '@/actions/App/Http/Controllers/TaskLabelController';
import TaskOrderController from '@/actions/App/Http/Controllers/TaskOrderController';
import TaskTagController from '@/actions/App/Http/Controllers/TaskTagController';
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
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAlerts } from '@/composables/useAlerts';
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

type Workspace = {
    id: number;
    name: string;
    slug: string;
    role?: string | null;
};

type WorkspaceMember = {
    id: number;
    name: string;
    email: string;
    role?: string | null;
    weekly_capacity_minutes?: number | null;
    is_active?: boolean | null;
};

type TaskLabel = {
    id: number;
    name: string;
    color: string;
};

type TaskTag = {
    id: number;
    name: string;
    color: string;
};

type Task = {
    id: number;
    title: string;
    description?: string | null;
    priority: string;
    starts_at?: string | null;
    ends_at?: string | null;
    column_id: number | null;
    sort_order: number;
    is_completed: boolean;
    completed_at?: string | null;
    assignees: WorkspaceMember[];
    labels: TaskLabel[];
    tags: TaskTag[];
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

type TaskComment = {
    id: number;
    body: string;
    created_at: string | null;
    user: WorkspaceMember;
    mentions: WorkspaceMember[];
};

type TaskActivity = {
    id: number;
    type: string;
    meta: Record<string, unknown> | null;
    created_at: string | null;
    user?: WorkspaceMember | null;
};

type Props = {
    workspaces: Workspace[];
    selectedWorkspaceId: number | null;
    boards: TaskBoard[];
    selectedBoardId: number | null;
    columns: TaskColumn[];
    labels: TaskLabel[];
    tags: TaskTag[];
    members: WorkspaceMember[];
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
const { toast } = useAlerts();

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

const priorityOptions = [
    { value: 'baixa', label: 'Baixa' },
    { value: 'normal', label: 'Normal' },
    { value: 'media', label: 'Média' },
    { value: 'alta', label: 'Alta' },
    { value: 'urgente', label: 'Urgente' },
    { value: 'critico', label: 'Crítico' },
];

const formatPriority = (value?: string | null) => {
    const option = priorityOptions.find((item) => item.value === value);
    return option?.label ?? 'Normal';
};

const formatActivityText = (activity: TaskActivity) => {
    switch (activity.type) {
        case 'created':
            return 'Criou esta task.';
        case 'status_changed':
            return `Moveu para ${activity.meta?.status ?? 'novo status'}.`;
        case 'completed':
            return 'Concluiu a task.';
        case 'overdue':
            return 'A task entrou em atraso.';
        case 'deleted':
            return 'Removeu a task.';
        case 'assigned': {
            const count = Array.isArray(activity.meta?.assignees)
                ? activity.meta.assignees.length
                : 0;
            return count > 0
                ? `Atribuiu ${count} responsável${count > 1 ? 'is' : ''}.`
                : 'Atualizou responsáveis.';
        }
        default:
            return 'Atualizou a task.';
    }
};

const escapeHtml = (value: string) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const highlightMentions = (value: string) =>
    escapeHtml(value).replace(
        /@([\\w._-]+)/g,
        '<span class="mention-pill">@$1</span>',
    );

const isOverdue = (task: Task) => {
    if (task.is_completed) {
        return false;
    }

    if (!task.ends_at) {
        return false;
    }

    const [year, month, day] = task.ends_at.split('-').map(Number);
    const endDate = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return endDate < today;
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
        onSuccess: () => {
            void toast({
                icon: 'success',
                title: `Timer iniciado: ${task.title}`,
            });
        },
    });
};

const stopTimer = (task: Task) => {
    router.patch(TaskTimerController.update(task.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            void toast({
                icon: 'success',
                title: `Timer pausado: ${task.title}`,
            });
        },
    });
};

const deleteTask = async (task: Task) => {
    const result = await Swal.fire({
        title: 'Excluir tarefa?',
        text: 'Essa ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        focusCancel: true,
    });

    if (!result.isConfirmed) {
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
const selectedLabels = ref<TaskLabel[]>([]);
const selectedTags = ref<TaskTag[]>([]);
const selectedAssignees = ref<WorkspaceMember[]>([]);
const labelOptions = ref<TaskLabel[]>([...props.labels]);
const tagOptions = ref<TaskTag[]>([...props.tags]);
const memberOptions = ref<WorkspaceMember[]>([...props.members]);

watch(
    () => props.labels,
    (value) => {
        labelOptions.value = [...value];
    },
);

watch(
    () => props.tags,
    (value) => {
        tagOptions.value = [...value];
    },
);

watch(
    () => props.members,
    (value) => {
        memberOptions.value = [...value];
    },
);

const getCsrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const normalizeOptionName = (name: string) =>
    name.trim().toLowerCase();

const colorPalette = [
    '#2563EB',
    '#0F766E',
    '#F97316',
    '#DB2777',
    '#7C3AED',
    '#059669',
    '#DC2626',
    '#0891B2',
    '#CA8A04',
    '#4F46E5',
    '#9333EA',
    '#16A34A',
];

const getUniqueColor = (options: Array<TaskLabel | TaskTag>) => {
    const used = new Set(options.map((option) => option.color.toUpperCase()));
    const available = colorPalette.filter(
        (color) => !used.has(color.toUpperCase()),
    );
    const pool = available.length ? available : colorPalette;
    const index = Math.floor(Math.random() * pool.length);

    return pool[index].toUpperCase();
};

const addOptionIfMissing = (
    options: typeof labelOptions.value,
    option: TaskLabel | TaskTag,
) => {
    const existing = options.find((item) => item.id === option.id);
    if (!existing) {
        options.push(option);
    }
};

const updateLocalLabel = (updated: TaskLabel) => {
    labelOptions.value = labelOptions.value.map((label) =>
        label.id === updated.id ? updated : label,
    );
    selectedLabels.value = selectedLabels.value.map((label) =>
        label.id === updated.id ? updated : label,
    );
};

const updateLocalTag = (updated: TaskTag) => {
    tagOptions.value = tagOptions.value.map((tag) =>
        tag.id === updated.id ? updated : tag,
    );
    selectedTags.value = selectedTags.value.map((tag) =>
        tag.id === updated.id ? updated : tag,
    );
};

const updateLabelColor = async (label: TaskLabel) => {
    const color = label.color.toUpperCase();
    const response = await fetch(TaskLabelController.update(label.id).url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ color }),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);
        const message = payload?.message ?? 'Não foi possível atualizar a label.';
        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message,
        });
        return;
    }

    const payload = await response.json();
    const updated = payload?.data?.data ?? payload?.data ?? payload;
    if (updated) {
        updateLocalLabel(updated);
    }
};

const updateTagColor = async (tag: TaskTag) => {
    const color = tag.color.toUpperCase();
    const response = await fetch(TaskTagController.update(tag.id).url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ color }),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);
        const message = payload?.message ?? 'Não foi possível atualizar a tag.';
        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message,
        });
        return;
    }

    const payload = await response.json();
    const updated = payload?.data?.data ?? payload?.data ?? payload;
    if (updated) {
        updateLocalTag(updated);
    }
};

const createLabelFromTag = async (name: string) => {
    const trimmed = name.trim();
    if (!trimmed) {
        return;
    }

    const existing = labelOptions.value.find(
        (option) => normalizeOptionName(option.name) === normalizeOptionName(trimmed),
    );
    if (existing) {
        if (!selectedLabels.value.some((label) => label.id === existing.id)) {
            selectedLabels.value = [...selectedLabels.value, existing];
        }
        return;
    }

    const response = await fetch(TaskLabelController.store().url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            name: trimmed,
            color: getUniqueColor(labelOptions.value),
        }),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);
        const message = payload?.message ?? 'Não foi possível criar a label.';
        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message,
        });
        return;
    }

    const payload = await response.json();
    const created = payload?.data?.data ?? payload?.data ?? payload;
    if (created) {
        addOptionIfMissing(labelOptions.value, created);
        selectedLabels.value = [...selectedLabels.value, created];
    }
};

const createTagFromTag = async (name: string) => {
    const trimmed = name.trim();
    if (!trimmed) {
        return;
    }

    const existing = tagOptions.value.find(
        (option) => normalizeOptionName(option.name) === normalizeOptionName(trimmed),
    );
    if (existing) {
        if (!selectedTags.value.some((tag) => tag.id === existing.id)) {
            selectedTags.value = [...selectedTags.value, existing];
        }
        return;
    }

    const response = await fetch(TaskTagController.store().url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            name: trimmed,
            color: getUniqueColor(tagOptions.value),
        }),
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);
        const message = payload?.message ?? 'Não foi possível criar a tag.';
        await Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message,
        });
        return;
    }

    const payload = await response.json();
    const created = payload?.data?.data ?? payload?.data ?? payload;
    if (created) {
        addOptionIfMissing(tagOptions.value, created);
        selectedTags.value = [...selectedTags.value, created];
    }
};

const reportUrl = computed(() => tasksReport().url);

const doneSlugs = ['done', 'concluido', 'concluida', 'concluidos', 'concluidas'];

const sortedWorkspaces = computed(() =>
    [...props.workspaces].sort((left, right) => left.name.localeCompare(right.name)),
);

const activeWorkspaceId = computed(
    () => props.selectedWorkspaceId ?? sortedWorkspaces.value[0]?.id ?? null,
);

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

const selectedTask = ref<Task | null>(null);
const isTaskModalOpen = ref(false);
const activeTaskChannelId = ref<number | null>(null);
const onlineMembers = ref<WorkspaceMember[]>([]);
const typingUsers = ref<Record<number, { name: string }>>({});
const lastTypingSentAt = ref<number>(0);
let typingClearTimer: number | undefined;
const taskComments = ref<TaskComment[]>([]);
const taskActivities = ref<TaskActivity[]>([]);
const taskDetailLoading = ref(false);
const newComment = ref('');
const taskDetailError = ref<string | null>(null);
const showMentionList = ref(false);
const mentionQuery = ref('');
const mentionSuggestions = ref<WorkspaceMember[]>([]);
const mentionActiveIndex = ref(0);
const mentionRange = ref<{ start: number; end: number } | null>(null);
const mentionTextarea = ref<HTMLTextAreaElement | null>(null);

const selectedTaskColumn = computed(() => {
    if (!selectedTask.value) {
        return null;
    }

    return (
        sortedColumns.value.find(
            (column) => column.id === selectedTask.value?.column_id,
        ) ?? null
    );
});

const activityFeed = computed(() => {
    const commentItems = taskComments.value.map((comment) => ({
        type: 'comment',
        created_at: comment.created_at,
        user: comment.user,
        comment,
    }));
    const activityItems = taskActivities.value
        .filter((activity) => activity.type !== 'commented')
        .map((activity) => ({
            type: 'activity',
            created_at: activity.created_at,
            user: activity.user ?? null,
            activity,
        }));

    return [...commentItems, ...activityItems].sort((left, right) => {
        const leftDate = left.created_at ? new Date(left.created_at).getTime() : 0;
        const rightDate = right.created_at ? new Date(right.created_at).getTime() : 0;
        return rightDate - leftDate;
    });
});

const loadTaskDetails = async (taskId: number) => {
    taskDetailLoading.value = true;
    taskDetailError.value = null;

    try {
        const response = await fetch(TaskCommentController.index(taskId).url, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Falha ao carregar comentários.');
        }

        const data = await response.json();

        taskComments.value = Array.isArray(data.comments) ? data.comments : [];
        taskActivities.value = Array.isArray(data.activities) ? data.activities : [];
    } catch (error) {
        taskDetailError.value =
            error instanceof Error ? error.message : 'Erro ao carregar detalhes.';
        Swal.fire({
            icon: 'error',
            title: 'Não foi possível carregar os detalhes',
            text: taskDetailError.value,
        });
    } finally {
        taskDetailLoading.value = false;
    }
};

const updateMentionSuggestions = (query: string) => {
    const normalized = query.toLowerCase();
    const candidates = props.members.filter((member) => {
        if (!normalized) {
            return true;
        }
        return (
            member.name.toLowerCase().includes(normalized) ||
            member.email.toLowerCase().includes(normalized)
        );
    });

    mentionSuggestions.value = candidates.slice(0, 6);
    mentionActiveIndex.value = 0;
    showMentionList.value = mentionSuggestions.value.length > 0;
};

const resolveMentionContext = (text: string, cursor: number) => {
    const beforeCursor = text.slice(0, cursor);
    const match = /(?:^|\\s)@([\\w._-]*)$/.exec(beforeCursor);

    if (!match) {
        mentionRange.value = null;
        showMentionList.value = false;
        return;
    }

    const query = match[1] ?? '';
    const start = beforeCursor.lastIndexOf('@');

    mentionQuery.value = query;
    mentionRange.value = {
        start,
        end: cursor,
    };

    updateMentionSuggestions(query);
};

const onCommentInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    mentionTextarea.value = target;
    resolveMentionContext(target.value, target.selectionStart ?? target.value.length);
    sendTypingSignal();
};

const applyMention = (member: WorkspaceMember) => {
    if (!mentionTextarea.value || !mentionRange.value) {
        return;
    }

    const value = mentionTextarea.value.value;
    const start = mentionRange.value.start;
    const end = mentionRange.value.end;
    const mentionText = `@${member.name.replace(/\s+/g, '')} `;

    const nextValue = `${value.slice(0, start)}${mentionText}${value.slice(end)}`;
    newComment.value = nextValue;

    const nextCursor = start + mentionText.length;
    requestAnimationFrame(() => {
        mentionTextarea.value?.setSelectionRange(nextCursor, nextCursor);
        mentionTextarea.value?.focus();
    });

    showMentionList.value = false;
    mentionRange.value = null;
};

const onCommentKeydown = (event: KeyboardEvent) => {
    if (!showMentionList.value) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        mentionActiveIndex.value =
            (mentionActiveIndex.value + 1) % mentionSuggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        mentionActiveIndex.value =
            (mentionActiveIndex.value - 1 + mentionSuggestions.value.length) %
            mentionSuggestions.value.length;
    } else if (event.key === 'Enter' || event.key === 'Tab') {
        event.preventDefault();
        const selected = mentionSuggestions.value[mentionActiveIndex.value];
        if (selected) {
            applyMention(selected);
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        showMentionList.value = false;
        mentionTextarea.value?.focus();
    }
};

const submitComment = async () => {
    if (!selectedTask.value) {
        return;
    }

    const body = newComment.value.trim();

    if (!body) {
        return;
    }

    showMentionList.value = false;

    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    try {
        const response = await fetch(
            TaskCommentController.store(selectedTask.value.id).url,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ body }),
            },
        );

        if (!response.ok) {
            throw new Error('Não foi possível salvar o comentário.');
        }

        const data = await response.json();

        if (data.comment) {
            taskComments.value = [...taskComments.value, data.comment];
        }

        if (data.activity) {
            taskActivities.value = [...taskActivities.value, data.activity];
        }

        newComment.value = '';
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro ao comentar',
            text:
                error instanceof Error
                    ? error.message
                    : 'Tente novamente em instantes.',
        });
    }
};

const addIncomingComment = (comment: TaskComment) => {
    if (taskComments.value.some((item) => item.id === comment.id)) {
        return;
    }

    taskComments.value = [...taskComments.value, comment];
};

const addIncomingActivity = (activity: TaskActivity) => {
    if (taskActivities.value.some((item) => item.id === activity.id)) {
        return;
    }

    taskActivities.value = [...taskActivities.value, activity];
};

const typingNames = computed(() => Object.values(typingUsers.value).map((user) => user.name));

const typingLabel = computed(() => {
    if (typingNames.value.length === 0) {
        return '';
    }

    if (typingNames.value.length === 1) {
        return `${typingNames.value[0]} digitando...`;
    }

    if (typingNames.value.length === 2) {
        return `${typingNames.value[0]} e ${typingNames.value[1]} digitando...`;
    }

    return `${typingNames.value.slice(0, 2).join(', ')} e outros digitando...`;
});

const handleTypingWhisper = (payload: { user?: WorkspaceMember }) => {
    if (!payload.user?.id) {
        return;
    }

    typingUsers.value = {
        ...typingUsers.value,
        [payload.user.id]: { name: payload.user.name },
    };

    if (typingClearTimer) {
        window.clearTimeout(typingClearTimer);
    }

    typingClearTimer = window.setTimeout(() => {
        typingUsers.value = {};
    }, 2500);
};

const sendTypingSignal = () => {
    if (!window.Echo || !activeTaskChannelId.value) {
        return;
    }

    const now = Date.now();
    if (now - lastTypingSentAt.value < 900) {
        return;
    }

    lastTypingSentAt.value = now;
    const auth = page.props.auth as { user?: WorkspaceMember } | undefined;
    const user = auth?.user;

    if (!user?.id) {
        return;
    }

    window.Echo.join(`tasks.${activeTaskChannelId.value}`).whisper('typing', {
        user: {
            id: user.id,
            name: user.name,
            email: user.email,
        },
    });
};

const subscribeToTaskChannel = (taskId: number) => {
    if (!window.Echo) {
        return;
    }

    if (activeTaskChannelId.value === taskId) {
        return;
    }

    if (activeTaskChannelId.value) {
        window.Echo.leave(`tasks.${activeTaskChannelId.value}`);
    }

    activeTaskChannelId.value = taskId;

    window.Echo.join(`tasks.${taskId}`)
        .here((members: WorkspaceMember[]) => {
            onlineMembers.value = members;
        })
        .joining((member: WorkspaceMember) => {
            onlineMembers.value = [...onlineMembers.value, member];
        })
        .leaving((member: WorkspaceMember) => {
            onlineMembers.value = onlineMembers.value.filter(
                (item) => item.id !== member.id,
            );
        })
        .listenForWhisper('typing', handleTypingWhisper)
        .listen('.task.comment.created', (payload: { comment: TaskComment }) => {
            if (payload?.comment) {
                addIncomingComment(payload.comment);
            }
        })
        .listen('.task.activity.created', (payload: { activity: TaskActivity }) => {
            if (payload?.activity) {
                addIncomingActivity(payload.activity);
            }
        });
};

const unsubscribeFromTaskChannel = () => {
    if (!window.Echo || !activeTaskChannelId.value) {
        activeTaskChannelId.value = null;
        return;
    }

    window.Echo.leave(`tasks.${activeTaskChannelId.value}`);
    activeTaskChannelId.value = null;
    onlineMembers.value = [];
    typingUsers.value = {};
};

const openTaskModal = (task: Task) => {
    if (dragType.value === 'task' && draggedTaskId.value === task.id) {
        return;
    }

    selectedTask.value = task;
    isTaskModalOpen.value = true;
    loadTaskDetails(task.id);
    subscribeToTaskChannel(task.id);
};

const openTaskFromQuery = () => {
    const url = new URL(page.url, window.location.origin);
    const taskId = Number(url.searchParams.get('task'));

    if (!Number.isFinite(taskId) || taskId <= 0) {
        return;
    }

    if (selectedTask.value?.id === taskId) {
        return;
    }

    const task = props.tasks.find((item) => item.id === taskId);

    if (!task) {
        void toast({
            icon: 'error',
            title: 'Task não encontrada no board atual.',
        });
        return;
    }

    openTaskModal(task);
};

onMounted(() => {
    openTaskFromQuery();
});

watch(
    () => page.url,
    () => {
        openTaskFromQuery();
    },
);

watch(isTaskModalOpen, (isOpen) => {
    if (!isOpen) {
        unsubscribeFromTaskChannel();
        selectedTask.value = null;
        taskComments.value = [];
        taskActivities.value = [];
        newComment.value = '';
        taskDetailError.value = null;
    }
});

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
        { board: boardId, workspace: activeWorkspaceId.value },
        { preserveScroll: true },
    );
};

const setWorkspace = (workspaceId: number) => {
    router.get(
        tasksIndex().url,
        { workspace: workspaceId },
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

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="border-input flex h-9 items-center gap-2 rounded-md border bg-transparent px-3 py-1 text-sm font-medium shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <span class="truncate">
                                            {{
                                                sortedWorkspaces.find(
                                                    (workspace) =>
                                                        workspace.id === activeWorkspaceId,
                                                )?.name ?? 'Workspace'
                                            }}
                                        </span>
                                        <ChevronsUpDown class="size-4 text-muted-foreground" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56"
                                    align="start"
                                >
                                    <DropdownMenuItem
                                        v-for="workspace in sortedWorkspaces"
                                        :key="workspace.id"
                                        @select="setWorkspace(workspace.id)"
                                    >
                                        {{ workspace.name }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <h2 class="text-2xl font-semibold tracking-tight">
                                Board: {{ activeBoard?.name ?? 'Padrão' }}
                            </h2>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="border-input flex h-9 items-center gap-2 rounded-md border bg-transparent px-3 py-1 text-sm font-medium shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <span class="truncate">
                                            {{ activeBoard?.name ?? 'Padrão' }}
                                        </span>
                                        <ChevronsUpDown class="size-4 text-muted-foreground" />
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
                                    class="cursor-pointer rounded-lg border border-border/70 bg-card/90 p-4 shadow-sm transition hover:border-primary/50 hover:shadow-md"
                                    :class="{
                                        'border-destructive/80 ring-1 ring-destructive/30':
                                            isOverdue(task),
                                    }"
                                    draggable="true"
                                    @dragstart="onDragStart(task)"
                                    @dragend="onDragEnd"
                                    @dragover.prevent
                                    @drop.stop="onDropTask(column.id, task.id)"
                                    @click="openTaskModal(task)"
                                >
                                    <div class="flex flex-col gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-sm font-semibold">
                                                    {{ task.title }}
                                                </h3>
                                                <Badge variant="outline">
                                                    {{ formatPriority(task.priority) }}
                                                </Badge>
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
                                                v-if="task.starts_at || task.ends_at"
                                                class="mt-2 text-[11px] text-muted-foreground"
                                            >
                                                Prazo:
                                                {{ task.starts_at ? formatDate(task.starts_at) : '—' }}
                                                →
                                                {{ task.ends_at ? formatDate(task.ends_at) : '—' }}
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

                                        <div
                                            v-if="task.labels.length || task.tags.length"
                                            class="flex flex-wrap gap-2 text-[11px]"
                                        >
                                            <span
                                                v-for="label in task.labels"
                                                :key="`label-${label.id}`"
                                                class="inline-flex items-center gap-1 rounded-full border border-border/70 px-2 py-0.5"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full"
                                                    :style="{ backgroundColor: label.color }"
                                                ></span>
                                                {{ label.name }}
                                            </span>
                                            <span
                                                v-for="tag in task.tags"
                                                :key="`tag-${tag.id}`"
                                                class="inline-flex items-center gap-1 rounded-full border border-border/70 px-2 py-0.5"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full"
                                                    :style="{ backgroundColor: tag.color }"
                                                ></span>
                                                {{ tag.name }}
                                            </span>
                                        </div>

                                        <div
                                            v-if="task.assignees.length"
                                            class="flex flex-wrap gap-2 text-[11px]"
                                        >
                                            <span
                                                v-for="assignee in task.assignees"
                                                :key="`assignee-${assignee.id}`"
                                                class="inline-flex items-center gap-1 rounded-full border border-border/70 px-2 py-0.5"
                                            >
                                                {{ assignee.name }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-2 text-[11px] text-muted-foreground">
                                            <span class="rounded-full border border-border/70 bg-card/80 px-2 py-0.5">
                                                Hoje: {{ formatSeconds(task.totals.daily) }}
                                            </span>
                                            <span class="rounded-full border border-border/70 bg-card/80 px-2 py-0.5">
                                                Semana: {{ formatSeconds(task.totals.weekly) }}
                                            </span>
                                            <span class="rounded-full border border-border/70 bg-card/80 px-2 py-0.5">
                                                Mês: {{ formatSeconds(task.totals.monthly) }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                v-if="!task.active_entry"
                                                size="sm"
                                                variant="secondary"
                                                @click.stop="startTimer(task)"
                                            >
                                                Start
                                            </Button>
                                            <Button
                                                v-else
                                                size="sm"
                                                variant="destructive"
                                                @click.stop="stopTimer(task)"
                                            >
                                                Stop
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click.stop="deleteTask(task)"
                                            >
                                                Delete
                                            </Button>
                                            <Button
                                                v-if="!task.is_completed"
                                                size="sm"
                                                variant="outline"
                                                @click.stop="completeTask(task)"
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

                <aside class="flex flex-col gap-6">
                    <Card class="border-border/70 bg-card/80 shadow-sm">
                        <CardHeader>
                            <CardTitle>Nova tarefa</CardTitle>
                            <CardDescription>
                                Cadastre rapidamente e volte para o board.
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
                                <div class="grid gap-2">
                                    <label class="text-xs font-medium">
                                        Prioridade
                                    </label>
                                    <select
                                        name="priority"
                                        class="border-input flex h-9 w-full rounded-md border bg-card px-3 py-1 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        style="color-scheme: dark;"
                                    >
                                        <option
                                            v-for="priority in priorityOptions"
                                            :key="priority.value"
                                            :value="priority.value"
                                            :selected="priority.value === 'normal'"
                                        >
                                            {{ priority.label }}
                                        </option>
                                    </select>
                                    <InputError :message="errors.priority" />
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <label class="text-xs font-medium">
                                            Início
                                        </label>
                                        <Input name="starts_at" type="date" />
                                        <InputError :message="errors.starts_at" />
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="text-xs font-medium">
                                            Fim
                                        </label>
                                        <Input name="ends_at" type="date" />
                                        <InputError :message="errors.ends_at" />
                                    </div>
                                </div>
                                <div class="grid gap-3">
                                    <div class="grid gap-2">
                                        <label class="text-xs font-medium">
                                            Tags
                                        </label>
                                        <Multiselect
                                            v-model="selectedTags"
                                            :options="tagOptions"
                                            :multiple="true"
                                            track-by="id"
                                            label="name"
                                            placeholder="Selecione tags"
                                            :close-on-select="false"
                                            :clear-on-select="false"
                                            :preserve-search="true"
                                            :taggable="true"
                                            tag-placeholder="Criar"
                                            @tag="createTagFromTag"
                                        />
                                        <input
                                            v-for="tag in selectedTags"
                                            :key="`tag-input-${tag.id}`"
                                            type="hidden"
                                            name="tags[]"
                                            :value="tag.id"
                                        />
                                        <InputError :message="errors.tags" />
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="text-xs font-medium">
                                            Labels
                                        </label>
                                        <Multiselect
                                            v-model="selectedLabels"
                                            :options="labelOptions"
                                            :multiple="true"
                                            track-by="id"
                                            label="name"
                                            placeholder="Selecione labels"
                                            :close-on-select="false"
                                            :clear-on-select="false"
                                            :preserve-search="true"
                                            :taggable="true"
                                            tag-placeholder="Criar"
                                            @tag="createLabelFromTag"
                                        />
                                        <input
                                            v-for="label in selectedLabels"
                                            :key="`label-input-${label.id}`"
                                            type="hidden"
                                            name="labels[]"
                                            :value="label.id"
                                        />
                                        <InputError :message="errors.labels" />
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="text-xs font-medium">
                                            Responsáveis
                                        </label>
                                        <Multiselect
                                            v-model="selectedAssignees"
                                            :options="memberOptions"
                                            :multiple="true"
                                            track-by="id"
                                            label="name"
                                            placeholder="Selecione responsáveis"
                                            :close-on-select="false"
                                            :clear-on-select="false"
                                            :preserve-search="true"
                                        />
                                        <input
                                            v-for="assignee in selectedAssignees"
                                            :key="`assignee-input-${assignee.id}`"
                                            type="hidden"
                                            name="assignees[]"
                                            :value="assignee.id"
                                        />
                                        <InputError :message="errors.assignees" />
                                    </div>
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
                            <CardTitle>Ações rápidas</CardTitle>
                            <CardDescription>
                                Relatórios e ajustes do board em um único lugar.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <details class="group rounded-lg border border-border/70 bg-card/70 p-3">
                                <summary class="flex cursor-pointer items-center justify-between text-sm font-medium">
                                    Exportar relatório
                                    <span class="text-xs text-muted-foreground group-open:hidden">
                                        Abrir
                                    </span>
                                </summary>
                                <div class="mt-3">
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
                                </div>
                            </details>

                            <details class="group rounded-lg border border-border/70 bg-card/70 p-3">
                                <summary class="flex cursor-pointer items-center justify-between text-sm font-medium">
                                    Gerenciar status
                                    <span class="text-xs text-muted-foreground group-open:hidden">
                                        Abrir
                                    </span>
                                </summary>
                                <div class="mt-3">
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
                                </div>
                            </details>

                            <details class="group rounded-lg border border-border/70 bg-card/70 p-3">
                                <summary class="flex cursor-pointer items-center justify-between text-sm font-medium">
                                    Labels & Tags
                                    <span class="text-xs text-muted-foreground group-open:hidden">
                                        Abrir
                                    </span>
                                </summary>
                                <div class="mt-3 grid gap-4">
                                    <Form
                                        v-bind="TaskLabelController.store.form()"
                                        class="flex flex-col gap-4"
                                        v-slot="{ errors, processing, recentlySuccessful }"
                                    >
                                        <div class="grid gap-2">
                                            <Input
                                                name="name"
                                                placeholder="Nome da label"
                                                required
                                            />
                                            <InputError :message="errors.name" />
                                        </div>
                                        <div class="grid gap-2">
                                            <label class="text-xs font-medium">
                                                Cor
                                            </label>
                                            <input
                                                type="color"
                                                name="color"
                                                value="#1D4ED8"
                                                class="h-9 w-full rounded-md border border-input bg-transparent p-1"
                                            />
                                            <InputError :message="errors.color" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Button type="submit" :disabled="processing">
                                                Criar label
                                            </Button>
                                            <span
                                                v-if="recentlySuccessful"
                                                class="text-sm text-muted-foreground"
                                            >
                                                Criada!
                                            </span>
                                        </div>
                                    </Form>

                                    <Form
                                        v-bind="TaskTagController.store.form()"
                                        class="flex flex-col gap-4"
                                        v-slot="{ errors, processing, recentlySuccessful }"
                                    >
                                        <div class="grid gap-2">
                                            <Input
                                                name="name"
                                                placeholder="Nome da tag"
                                                required
                                            />
                                            <InputError :message="errors.name" />
                                        </div>
                                        <div class="grid gap-2">
                                            <label class="text-xs font-medium">
                                                Cor
                                            </label>
                                            <input
                                                type="color"
                                                name="color"
                                                value="#0F766E"
                                                class="h-9 w-full rounded-md border border-input bg-transparent p-1"
                                            />
                                            <InputError :message="errors.color" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Button type="submit" :disabled="processing">
                                                Criar tag
                                            </Button>
                                            <span
                                                v-if="recentlySuccessful"
                                                class="text-sm text-muted-foreground"
                                            >
                                                Criada!
                                            </span>
                                        </div>
                                    </Form>

                                    <div class="grid gap-3 border-t border-border/70 pt-3 text-sm">
                                        <div>
                                            <p class="text-xs font-medium text-muted-foreground">
                                                Ajustar cores existentes
                                            </p>
                                        </div>

                                        <div class="grid gap-2">
                                            <p class="text-xs font-medium">Tags</p>
                                            <div
                                                v-for="tag in tagOptions"
                                                :key="`tag-color-${tag.id}`"
                                                class="flex items-center justify-between gap-3 rounded-md border border-border/70 px-3 py-2"
                                            >
                                                <span class="text-xs font-medium">
                                                    {{ tag.name }}
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="tag.color"
                                                        type="color"
                                                        class="h-8 w-10 rounded-md border border-input bg-transparent p-1"
                                                    />
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="updateTagColor(tag)"
                                                    >
                                                        Salvar
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-2">
                                            <p class="text-xs font-medium">Labels</p>
                                            <div
                                                v-for="label in labelOptions"
                                                :key="`label-color-${label.id}`"
                                                class="flex items-center justify-between gap-3 rounded-md border border-border/70 px-3 py-2"
                                            >
                                                <span class="text-xs font-medium">
                                                    {{ label.name }}
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="label.color"
                                                        type="color"
                                                        class="h-8 w-10 rounded-md border border-input bg-transparent p-1"
                                                    />
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="updateLabelColor(label)"
                                                    >
                                                        Salvar
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
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
                </aside>
            </div>
        </div>

        <Dialog :open="isTaskModalOpen" @update:open="isTaskModalOpen = $event">
            <DialogScrollContent class="max-w-5xl gap-6 border-border/70 bg-card/95 text-foreground shadow-xl backdrop-blur">
                <div v-if="selectedTask" class="grid gap-6 md:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="space-y-6">
                        <DialogHeader class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span class="rounded-full border border-border/70 bg-card/70 px-3 py-1">
                                    {{ selectedTaskColumn?.name ?? 'Sem status' }}
                                </span>
                                <Badge variant="outline">
                                    {{ formatPriority(selectedTask.priority) }}
                                </Badge>
                                <Badge v-if="selectedTask.active_entry" variant="secondary">
                                    Rodando
                                </Badge>
                                <Badge v-if="isOverdue(selectedTask)" variant="destructive">
                                    Atrasada
                                </Badge>
                            </div>
                            <DialogTitle class="text-2xl font-semibold">
                                {{ selectedTask.title }}
                            </DialogTitle>
                            <DialogDescription class="text-sm text-muted-foreground">
                                Detalhes da tarefa e atividades recentes.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Descrição
                            </p>
                            <div
                                class="rounded-lg border border-border/70 bg-card/80 p-4 text-sm text-muted-foreground"
                            >
                                {{ selectedTask.description || 'Sem descrição adicionada.' }}
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Datas
                                </p>
                                <div class="rounded-lg border border-border/70 bg-card/80 p-4 text-xs text-muted-foreground">
                                    <div class="flex items-center justify-between">
                                        <span>Início</span>
                                        <span class="font-semibold text-foreground">
                                            {{ selectedTask.starts_at ? formatDate(selectedTask.starts_at) : '—' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span>Fim</span>
                                        <span class="font-semibold text-foreground">
                                            {{ selectedTask.ends_at ? formatDate(selectedTask.ends_at) : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Tempo
                                </p>
                                <div class="rounded-lg border border-border/70 bg-card/80 p-4 text-xs text-muted-foreground">
                                    <div class="flex items-center justify-between">
                                        <span>Hoje</span>
                                        <span class="font-semibold text-foreground">
                                            {{ formatSeconds(selectedTask.totals.daily) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span>Semana</span>
                                        <span class="font-semibold text-foreground">
                                            {{ formatSeconds(selectedTask.totals.weekly) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span>Mês</span>
                                        <span class="font-semibold text-foreground">
                                            {{ formatSeconds(selectedTask.totals.monthly) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Labels
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="label in selectedTask.labels"
                                        :key="`modal-label-${label.id}`"
                                        class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-card/80 px-2 py-1 text-xs"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: label.color }"
                                        ></span>
                                        {{ label.name }}
                                    </span>
                                    <span v-if="!selectedTask.labels.length" class="text-xs text-muted-foreground">
                                        Nenhuma label atribuída.
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Tags
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="tag in selectedTask.tags"
                                        :key="`modal-tag-${tag.id}`"
                                        class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-card/80 px-2 py-1 text-xs"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: tag.color }"
                                        ></span>
                                        {{ tag.name }}
                                    </span>
                                    <span v-if="!selectedTask.tags.length" class="text-xs text-muted-foreground">
                                        Nenhuma tag atribuída.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Responsáveis
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="assignee in selectedTask.assignees"
                                    :key="`modal-assignee-${assignee.id}`"
                                    class="inline-flex items-center rounded-full border border-border/70 bg-card/80 px-2 py-1 text-xs"
                                >
                                    {{ assignee.name }}
                                </span>
                                <span v-if="!selectedTask.assignees.length" class="text-xs text-muted-foreground">
                                    Nenhum responsável definido.
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-lg border border-border/70 bg-card/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Ações rápidas
                            </p>
                            <div class="mt-3 grid gap-2">
                                <Button
                                    v-if="!selectedTask.active_entry"
                                    variant="secondary"
                                    @click="startTimer(selectedTask)"
                                >
                                    Iniciar timer
                                </Button>
                                <Button
                                    v-else
                                    variant="destructive"
                                    @click="stopTimer(selectedTask)"
                                >
                                    Parar timer
                                </Button>
                                <Button
                                    v-if="!selectedTask.is_completed"
                                    variant="outline"
                                    @click="completeTask(selectedTask)"
                                >
                                    Concluir tarefa
                                </Button>
                                <Button variant="ghost" @click="deleteTask(selectedTask)">
                                    Excluir tarefa
                                </Button>
                            </div>
                        </div>

                        <div class="rounded-lg border border-border/70 bg-card/80 p-4 text-xs text-muted-foreground">
                            <p class="font-semibold uppercase tracking-wide text-muted-foreground">
                                Comentários e atividade
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                                <span>Online</span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div
                                        v-for="member in onlineMembers"
                                        :key="`online-${member.id}`"
                                        class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-card/70 px-2 py-0.5"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <span class="text-foreground/80">{{ member.name }}</span>
                                    </div>
                                    <span v-if="!onlineMembers.length" class="text-muted-foreground">
                                        ninguém online
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3 space-y-3">
                                <textarea
                                    ref="mentionTextarea"
                                    v-model="newComment"
                                    rows="3"
                                    class="min-h-[96px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Escrever um comentário... use @ para mencionar"
                                    @input="onCommentInput"
                                    @keydown="onCommentKeydown"
                                />
                                <div
                                    v-if="showMentionList"
                                    class="rounded-lg border border-border/70 bg-card/95 p-2 shadow-lg"
                                >
                                    <p class="px-2 pb-1 text-[11px] text-muted-foreground">
                                        Selecionar usuário
                                    </p>
                                    <button
                                        v-for="(member, index) in mentionSuggestions"
                                        :key="`mention-${member.id}`"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-md px-2 py-1 text-left text-xs transition hover:bg-muted/60"
                                        :class="{
                                            'bg-muted/70 text-foreground': index === mentionActiveIndex,
                                        }"
                                        @click="applyMention(member)"
                                    >
                                        <span class="font-medium text-foreground">
                                            {{ member.name }}
                                        </span>
                                        <span class="text-[11px] text-muted-foreground">
                                            {{ member.email }}
                                        </span>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-muted-foreground">
                                        Mencione usando @nome ou @email
                                    </span>
                                    <span class="text-[11px] text-emerald-300">
                                        {{ typingLabel }}
                                    </span>
                                    <Button size="sm" variant="secondary" @click="submitComment">
                                        Comentar
                                    </Button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div v-if="taskDetailLoading" class="text-xs text-muted-foreground">
                                    Carregando comentários...
                                </div>
                                <div v-else-if="taskDetailError" class="text-xs text-destructive">
                                    {{ taskDetailError }}
                                </div>
                                <div
                                    v-else
                                    class="space-y-3"
                                >
                                    <div
                                        v-for="item in activityFeed"
                                        :key="`${item.type}-${item.type === 'comment' ? item.comment.id : item.activity.id}`"
                                        class="rounded-lg border border-border/70 bg-card/90 p-3"
                                    >
                                        <div class="flex items-center justify-between text-[11px] text-muted-foreground">
                                            <span class="font-semibold text-foreground">
                                                {{ item.user?.name ?? 'Sistema' }}
                                            </span>
                                            <span>
                                                {{ item.created_at ? formatDateTime(item.created_at) : 'agora' }}
                                            </span>
                                        </div>
                                        <div v-if="item.type === 'comment'" class="mt-2 text-sm text-foreground">
                                            <span v-html="highlightMentions(item.comment.body)"></span>
                                        </div>
                                        <div v-if="item.type === 'comment' && item.comment.mentions.length" class="mt-2 flex flex-wrap gap-2 text-[11px]">
                                            <span
                                                v-for="mention in item.comment.mentions"
                                                :key="`mention-${item.comment.id}-${mention.id}`"
                                                class="rounded-full border border-border/70 bg-card/80 px-2 py-0.5 text-foreground"
                                            >
                                                @{{ mention.name }}
                                            </span>
                                        </div>
                                        <div v-if="item.type === 'activity'" class="mt-2 text-sm text-muted-foreground">
                                            {{ formatActivityText(item.activity) }}
                                        </div>
                                    </div>
                                    <div v-if="!activityFeed.length" class="text-xs text-muted-foreground">
                                        Nenhuma atividade registrada ainda.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-border/70 bg-card/80 p-4 text-xs text-muted-foreground">
                            <p class="font-semibold uppercase tracking-wide text-muted-foreground">
                                Detalhes
                            </p>
                            <div class="mt-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span>Status atual</span>
                                    <span class="font-semibold text-foreground">
                                        {{ selectedTaskColumn?.name ?? '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Prioridade</span>
                                    <span class="font-semibold text-foreground">
                                        {{ formatPriority(selectedTask.priority) }}
                                    </span>
                                </div>
                                <div v-if="selectedTask.active_entry" class="text-[11px]">
                                    Iniciado {{ formatDateTime(selectedTask.active_entry.started_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogScrollContent>
        </Dialog>
    </AppLayout>
</template>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<style>
.mention-pill {
    color: #8b5cf6;
    font-weight: 600;
}
</style>
