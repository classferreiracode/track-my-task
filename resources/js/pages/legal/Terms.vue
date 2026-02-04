<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CookieConsent from '@/components/CookieConsent.vue';
import { home } from '@/routes';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Termos de Uso" />

        <header class="border-b border-border/70 bg-background/80">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-semibold">Track my Task</p>
                    <p class="text-xs text-muted-foreground">Termos de Uso</p>
                </div>
                <Link :href="home()" class="text-sm text-muted-foreground hover:text-foreground">
                    Voltar para home
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-6 py-10">
            <div class="space-y-6 text-sm text-muted-foreground">
                <h1 class="text-2xl font-semibold text-foreground">Termos de Uso</h1>
                <p>
                    Estes termos regem o uso do Track my Task. Ao criar uma conta, voce confirma a
                    concordancia com estas condicoes.
                </p>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Uso permitido</h2>
                    <p>
                        O sistema deve ser utilizado apenas para fins legitimos de gestao de tarefas,
                        tempo e produtividade.
                    </p>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Responsabilidades</h2>
                    <p>
                        Cada usuario e responsavel pelas informacoes cadastradas e pelo uso adequado de
                        suas credenciais de acesso.
                    </p>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Suspensao e encerramento</h2>
                    <p>
                        Podemos suspender contas em casos de violacao destes termos ou uso indevido do
                        sistema.
                    </p>
                </div>
            </div>
        </main>

        <CookieConsent />
    </div>
</template>
