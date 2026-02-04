<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, CheckCircle2, Clock3, FolderKanban } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ContactForm from '@/components/ContactForm.vue';
import CookieConsent from '@/components/CookieConsent.vue';
import HomeChatbot from '@/components/HomeChatbot.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { dashboard, login, privacy, register, terms } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const isBusinessModalOpen = ref(false);
const chatbotRef = ref<InstanceType<typeof HomeChatbot> | null>(null);

const openSalesChat = () => {
    chatbotRef.value?.openSalesFlow();
};
</script>

<template>
    <Head title="Track my Task" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border/70 bg-background/80 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                        <AppLogoIcon class="size-5 text-primary" />
                    </span>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold">Track my Task</p>
                        <p class="text-xs text-muted-foreground">Gestao de tarefas e horas</p>
                    </div>
                </div>
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md border border-border/70 px-4 py-2 text-sm font-medium hover:border-border"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                        >
                            Entrar
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm"
                        >
                            Criar conta
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 py-16">
            <section class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">
                        Plataforma corporativa
                    </p>
                    <h1 class="text-4xl font-semibold leading-tight text-foreground sm:text-5xl">
                        Controle profissional de tarefas, horas e relatorios.
                    </h1>
                    <p class="text-base text-muted-foreground">
                        Centralize projetos, acompanhe produtividade e gere relatorios detalhados
                        para equipes e lideranca com rapidez e clareza.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground"
                        >
                            Acessar dashboard
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground"
                        >
                            Entrar agora
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md border border-border/70 px-5 py-2.5 text-sm font-medium"
                        >
                            Conhecer recursos
                        </Link>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/70 p-4">
                            <FolderKanban class="mt-1 size-4 text-primary" />
                            <div>
                                <p class="text-sm font-medium">Boards por projeto</p>
                                <p class="text-xs text-muted-foreground">
                                    Status personalizaveis e prioridades claras.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/70 p-4">
                            <Clock3 class="mt-1 size-4 text-primary" />
                            <div>
                                <p class="text-sm font-medium">Controle de tempo</p>
                                <p class="text-xs text-muted-foreground">
                                    Registro preciso de play e pause por tarefa.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/70 p-4">
                            <BarChart3 class="mt-1 size-4 text-primary" />
                            <div>
                                <p class="text-sm font-medium">KPIs executivos</p>
                                <p class="text-xs text-muted-foreground">
                                    Visao diaria, semanal e mensal do time.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-border/70 bg-card/70 p-4">
                            <CheckCircle2 class="mt-1 size-4 text-primary" />
                            <div>
                                <p class="text-sm font-medium">Relatorios CSV</p>
                                <p class="text-xs text-muted-foreground">
                                    Exportacao pronta para financeiro e RH.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-border/70 bg-card/80 p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Visao rapida
                        </p>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-lg border border-border/70 bg-muted/40 p-4">
                                <p class="text-sm font-medium">Tasks em andamento</p>
                                <p class="mt-2 text-3xl font-semibold">18</p>
                                <p class="text-xs text-muted-foreground">+3 desde ontem</p>
                            </div>
                            <div class="rounded-lg border border-border/70 bg-muted/40 p-4">
                                <p class="text-sm font-medium">Horas registradas</p>
                                <p class="mt-2 text-3xl font-semibold">124h</p>
                                <p class="text-xs text-muted-foreground">Periodo atual</p>
                            </div>
                            <div class="rounded-lg border border-border/70 bg-muted/40 p-4">
                                <p class="text-sm font-medium">Conclusoes</p>
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="h-2.5 flex-1 rounded-full bg-muted">
                                        <span class="block h-2.5 w-3/4 rounded-full bg-primary/80"></span>
                                    </span>
                                    <span class="text-xs font-medium">75%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-border/70 bg-primary/10 p-6">
                        <p class="text-sm font-medium">Pronto para comecar?</p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            Cadastre sua equipe e acompanhe resultados em tempo real.
                        </p>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                        >
                            Criar workspace
                        </Link>
                    </div>
                </div>
            </section>

            <section class="mt-16">
                <div class="flex flex-col gap-3 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">
                        Planos
                    </p>
                    <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                        Escolha o plano ideal para o seu time
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Limites por workspace com foco em produtividade e controle de custos.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl border border-border/70 bg-card/70 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold">Free</p>
                                <p class="text-xs text-muted-foreground">Para comecar</p>
                            </div>
                            <span class="text-xs font-semibold uppercase text-muted-foreground">
                                Gratis
                            </span>
                        </div>
                        <div class="mt-6 space-y-3 text-sm text-muted-foreground">
                            <p>Até 3 membros</p>
                            <p>Até 3 boards</p>
                            <p>1 exportacao por mes</p>
                            <p>1 timer ativo por usuario</p>
                        </div>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                        >
                            Criar conta
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-md border border-border/70 px-4 py-2 text-sm font-medium"
                        >
                            Acessar
                        </Link>
                    </div>

                    <div class="rounded-2xl border border-primary/30 bg-primary/10 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold">Pro</p>
                                <p class="text-xs text-muted-foreground">Times em crescimento</p>
                            </div>
                            <span class="rounded-full bg-primary/20 px-3 py-1 text-[10px] font-semibold uppercase text-primary">
                                Mais escolhido
                            </span>
                        </div>
                        <div class="mt-6 space-y-3 text-sm text-muted-foreground">
                            <p>Até 10 membros</p>
                            <p>Até 10 boards</p>
                            <p>50 exportacoes por mes</p>
                            <p>3 timers ativos por usuario</p>
                        </div>
                        <Link
                            :href="register()"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                        >
                            Fazer upgrade
                        </Link>
                    </div>

                    <div class="rounded-2xl border border-border/70 bg-card/70 p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold">Business</p>
                                <p class="text-xs text-muted-foreground">Operacao completa</p>
                            </div>
                            <span class="text-xs font-semibold uppercase text-muted-foreground">
                                Enterprise
                            </span>
                        </div>
                        <div class="mt-6 space-y-3 text-sm text-muted-foreground">
                            <p>Até 50 membros</p>
                            <p>Até 50 boards</p>
                            <p>500 exportacoes por mes</p>
                            <p>10 timers ativos por usuario</p>
                        </div>
                        <Link
                            :href="register()"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-md border border-border/70 px-4 py-2 text-sm font-medium"
                        >
                            Falar com vendas
                        </Link>
                        <Button
                            type="button"
                            variant="outline"
                            class="mt-3 w-full"
                            @click="openSalesChat"
                        >
                            Falar com vendas
                        </Button>
                    </div>
                </div>

                <div class="mt-12 rounded-2xl border border-border/70 bg-card/70 p-6 shadow-sm">
                    <div class="flex flex-col gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">
                            Comparador
                        </p>
                        <h3 class="text-2xl font-semibold tracking-tight">
                            Compare recursos lado a lado
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Entenda rapidamente os limites por workspace.
                        </p>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-border/70 text-xs uppercase text-muted-foreground">
                                    <th class="py-3 pr-4">Recursos</th>
                                    <th class="py-3 pr-4">Free</th>
                                    <th class="py-3 pr-4">Pro</th>
                                    <th class="py-3">Business</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <tr class="border-b border-border/70">
                                    <td class="py-3 pr-4 font-medium">Membros</td>
                                    <td class="py-3 pr-4">3</td>
                                    <td class="py-3 pr-4">10</td>
                                    <td class="py-3">50</td>
                                </tr>
                                <tr class="border-b border-border/70">
                                    <td class="py-3 pr-4 font-medium">Boards</td>
                                    <td class="py-3 pr-4">3</td>
                                    <td class="py-3 pr-4">10</td>
                                    <td class="py-3">50</td>
                                </tr>
                                <tr class="border-b border-border/70">
                                    <td class="py-3 pr-4 font-medium">Exportacoes/mês</td>
                                    <td class="py-3 pr-4">1</td>
                                    <td class="py-3 pr-4">50</td>
                                    <td class="py-3">500</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-medium">Timers ativos/usuário</td>
                                    <td class="py-3 pr-4">1</td>
                                    <td class="py-3 pr-4">3</td>
                                    <td class="py-3">10</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-12 rounded-2xl border border-border/70 bg-card/80 p-6 shadow-sm">
                    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">
                                Contato
                            </p>
                            <h3 class="text-2xl font-semibold tracking-tight">
                                Fale com nosso time
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Conte o tamanho do seu time e objetivos para receber uma proposta do plano Business.
                            </p>
                            <div class="rounded-xl border border-border/70 bg-muted/40 p-4 text-xs text-muted-foreground">
                                SLA de resposta em até 1 dia util.
                            </div>
                        </div>
                        <div>
                            <ContactForm submit-label="Enviar pedido" />
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-border/70 bg-background/80">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-6 py-6 text-xs text-muted-foreground md:flex-row md:items-center md:justify-between">
                <p>Track my Task - Gestao corporativa de produtividade.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="terms()" class="hover:text-foreground">
                        Termos de Uso
                    </Link>
                    <Link :href="privacy()" class="hover:text-foreground">
                        Politica de Privacidade
                    </Link>
                </div>
            </div>
        </footer>
    </div>

    <Dialog v-model:open="isBusinessModalOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Plano Business</DialogTitle>
                <DialogDescription>
                    Envie suas informacoes e retornaremos com uma proposta.
                </DialogDescription>
            </DialogHeader>
            <ContactForm
                submit-label="Enviar contato"
                @submitted="isBusinessModalOpen = false"
            />
        </DialogContent>
    </Dialog>

    <HomeChatbot ref="chatbotRef" />
    <CookieConsent />
</template>
