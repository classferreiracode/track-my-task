<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, logout } from '@/routes/management';
import { index as plansIndex } from '@/routes/management/plans';
import { index as usersIndex } from '@/routes/management/users';

defineProps<{
    title: string;
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border/70 bg-background/80">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                <div>
                    <p class="text-sm font-semibold">Management</p>
                    <p class="text-xs text-muted-foreground">Painel do sistema</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">{{ title }}</span>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="logout()" method="post" as="button">
                            <LogOut class="mr-2 size-4" />
                            Sair
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-8 lg:flex-row">
            <aside class="w-full max-w-xl lg:w-56">
                <nav class="flex flex-col gap-2 text-sm">
                    <Link
                        :href="dashboard().url"
                        :class="[
                            'rounded-md border px-3 py-2',
                            isCurrentUrl(dashboard().url)
                                ? 'border-primary/60 bg-primary/10 text-foreground'
                                : 'border-border/70 text-muted-foreground hover:border-border hover:text-foreground',
                        ]"
                    >
                        Dashboard
                    </Link>
                    <Link
                        :href="usersIndex().url"
                        :class="[
                            'rounded-md border px-3 py-2',
                            isCurrentUrl(usersIndex().url)
                                ? 'border-primary/60 bg-primary/10 text-foreground'
                                : 'border-border/70 text-muted-foreground hover:border-border hover:text-foreground',
                        ]"
                    >
                        Usuarios
                    </Link>
                    <Link
                        :href="plansIndex().url"
                        :class="[
                            'rounded-md border px-3 py-2',
                            isCurrentUrl(plansIndex().url)
                                ? 'border-primary/60 bg-primary/10 text-foreground'
                                : 'border-border/70 text-muted-foreground hover:border-border hover:text-foreground',
                        ]"
                    >
                        Planos
                    </Link>
                    <div class="rounded-md border border-dashed border-border/70 px-3 py-2 text-xs text-muted-foreground">
                        Tickets (em breve)
                    </div>
                    <div class="rounded-md border border-dashed border-border/70 px-3 py-2 text-xs text-muted-foreground">
                        Financeiro (em breve)
                    </div>
                </nav>
            </aside>

            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
