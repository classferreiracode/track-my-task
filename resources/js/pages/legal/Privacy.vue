<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CookieConsent from '@/components/CookieConsent.vue';
import { home } from '@/routes';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Politica de Privacidade" />

        <header class="border-b border-border/70 bg-background/80">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-semibold">Track my Task</p>
                    <p class="text-xs text-muted-foreground">Politica de Privacidade</p>
                </div>
                <Link :href="home()" class="text-sm text-muted-foreground hover:text-foreground">
                    Voltar para home
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-6 py-10">
            <div class="space-y-6 text-sm text-muted-foreground">
                <h1 class="text-2xl font-semibold text-foreground">Politica de Privacidade</h1>
                <p>
                    Esta politica descreve como coletamos, usamos e protegemos seus dados pessoais em
                    conformidade com a LGPD. Ao utilizar o Track my Task, voce concorda com as praticas
                    descritas aqui.
                </p>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Dados coletados</h2>
                    <p>
                        Coletamos dados cadastrais (nome, email, empresa), dados de uso do sistema e
                        informacoes tecnicas essenciais para autenticacao e seguranca.
                    </p>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Finalidades</h2>
                    <p>
                        Utilizamos os dados para prover o servico, gerar relatorios, melhorar a
                        experiencia e cumprir obrigacoes legais.
                    </p>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Cookies</h2>
                    <p>
                        Usamos cookies essenciais para sessao e preferencia. Voce pode aceitar ou recusar
                        cookies adicionais por meio do banner de consentimento.
                    </p>
                </div>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold text-foreground">Seus direitos</h2>
                    <p>
                        Voce pode solicitar acesso, correcao ou exclusao de dados pessoais. Para isso,
                        entre em contato conosco.
                    </p>
                </div>
            </div>
        </main>

        <CookieConsent />
    </div>
</template>
