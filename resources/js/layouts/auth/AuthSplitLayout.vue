<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Award, BarChart3, ShieldCheck } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid min-h-dvh flex-col items-center justify-center px-6 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col overflow-hidden bg-slate-950 p-12 text-white lg:flex"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800"
            />
            <div
                class="absolute -right-28 top-10 h-72 w-72 rounded-full bg-blue-600/25 blur-3xl"
            />
            <div
                class="absolute -left-16 bottom-10 h-60 w-60 rounded-full bg-sky-500/20 blur-3xl"
            />
            <Link
                :href="home()"
                class="relative z-20 flex items-center gap-3 text-lg font-semibold tracking-tight"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10"
                >
                    <AppLogoIcon class="size-6 text-white" />
                </span>
                <span>{{ name }}</span>
            </Link>
            <div class="relative z-20 mt-auto space-y-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-300">
                        Workspace corporativo
                    </p>
                    <h2 class="mt-4 text-3xl font-semibold leading-tight">
                        Controle estratégico de tarefas e horas.
                    </h2>
                    <p class="mt-3 text-sm text-slate-300">
                        Centralize equipes, acompanhe produtividade e gere relatórios
                        precisos para decisões rápidas.
                    </p>
                </div>
                <div class="grid gap-4 text-sm text-slate-200">
                    <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 p-3">
                        <BarChart3 class="size-4 text-sky-300" />
                        KPIs executivos em tempo real.
                    </div>
                    <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 p-3">
                        <Award class="size-4 text-sky-300" />
                        Boards por projeto com trilha completa de horas.
                    </div>
                    <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 p-3">
                        <ShieldCheck class="size-4 text-sky-300" />
                        Relatórios CSV prontos para o financeiro.
                    </div>
                </div>
            </div>
        </div>
        <div class="relative flex w-full items-center justify-center px-6 py-16 lg:p-16">
            <div
                class="relative mx-auto flex w-full max-w-md flex-col justify-center space-y-8 rounded-2xl border border-border/60 bg-white/90 p-8 shadow-2xl shadow-slate-200/60 backdrop-blur-sm dark:bg-slate-950/90 dark:shadow-black/40"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-2xl font-semibold tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
