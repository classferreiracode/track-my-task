<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAlerts } from '@/composables/useAlerts';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

type NotificationItem = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at?: string | null;
    created_at?: string | null;
};

const page = usePage();
const { toast } = useAlerts();

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

const propNotifications = computed<NotificationItem[]>(() => {
    const payload = page.props.notifications as
        | { items?: NotificationItem[] }
        | undefined;

    return payload?.items ?? [];
});

const propUnreadCount = computed(() => {
    const payload = page.props.notifications as
        | { unread_count?: number }
        | undefined;

    return payload?.unread_count ?? 0;
});

const notifications = ref<NotificationItem[]>([]);
const unreadCount = ref(0);

watch(
    propNotifications,
    (items) => {
        notifications.value = items;
    },
    { immediate: true },
);

watch(
    propUnreadCount,
    (count) => {
        unreadCount.value = count;
    },
    { immediate: true },
);

const showBadgePulse = computed(() => unreadCount.value > 0);

const formatNotificationText = (notification: NotificationItem) => {
    const data = notification.data ?? {};

    if (notification.type.includes('TaskMentioned')) {
        const taskTitle = (data.task_title as string) ?? 'uma tarefa';
        const actor = (data.actor as { name?: string })?.name ?? 'Alguém';

        return `${actor} mencionou você em ${taskTitle}.`;
    }

    return 'Você tem uma nova notificação.';
};

const formatNotificationDate = (date?: string | null) => {
    if (!date) {
        return '';
    }

    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(parsed);
};

const getNotificationUrl = (notification: NotificationItem) => {
    const url = notification.data?.url;
    return typeof url === 'string' && url.length > 0 ? url : null;
};

const markNotificationRead = (notification: NotificationItem) => {
    if (notification.read_at) {
        const url = getNotificationUrl(notification);
        if (url) {
            router.visit(url);
        }

        return;
    }

    router.post(NotificationController.read(notification.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            notifications.value = notifications.value.map((item) =>
                item.id === notification.id
                    ? { ...item, read_at: new Date().toISOString() }
                    : item,
            );
            unreadCount.value = Math.max(0, unreadCount.value - 1);
            const url = getNotificationUrl(notification);
            if (url) {
                router.visit(url);
            }
        },
    });
};

const markAllRead = () => {
    if (unreadCount.value === 0) {
        return;
    }

    router.post(NotificationController.readAll().url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            notifications.value = notifications.value.map((item) => ({
                ...item,
                read_at: item.read_at ?? new Date().toISOString(),
            }));
            unreadCount.value = 0;
        },
    });
};

const registerRealtimeNotifications = () => {
    const auth = page.props.auth as { user?: { id: number } } | undefined;
    const userId = auth?.user?.id;

    if (!userId || !window.Echo) {
        return;
    }

    window.Echo.private(`App.Models.User.${userId}`).notification(
        (notification: NotificationItem) => {
            const exists = notifications.value.some(
                (item) => item.id === notification.id,
            );

            if (exists) {
                return;
            }

            notifications.value = [notification, ...notifications.value].slice(0, 10);
            unreadCount.value += 1;
        },
    );
};

onMounted(() => {
    registerRealtimeNotifications();
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background/70 px-6 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="relative inline-flex size-9 items-center justify-center rounded-md border border-border/60 bg-card/70 text-muted-foreground transition hover:text-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <Bell class="size-4" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                            :class="showBadgePulse ? 'animate-pulse ring-2 ring-primary/40' : ''"
                        >
                            {{ unreadCount }}
                        </span>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <div class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-muted-foreground">
                        <span>Notificações</span>
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="markAllRead"
                        >
                            Marcar todas
                        </button>
                    </div>
                    <div v-if="!notifications.length" class="px-3 pb-3 text-sm text-muted-foreground">
                        Nenhuma notificação recente.
                    </div>
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col gap-1 text-sm"
                        @select="markNotificationRead(notification)"
                    >
                        <div
                            class="flex items-center justify-between gap-2"
                            :class="notification.read_at ? 'text-muted-foreground' : 'text-foreground'"
                        >
                            <span class="line-clamp-2">
                                {{ formatNotificationText(notification) }}
                            </span>
                        </div>
                        <span class="text-[11px] text-muted-foreground">
                            {{ formatNotificationDate(notification.created_at) }}
                        </span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
