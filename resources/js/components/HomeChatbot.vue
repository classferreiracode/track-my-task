<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type ChatMessage = {
    id: number;
    role: 'user' | 'bot';
    text: string;
};

const isOpen = ref(false);
const input = ref('');
const agentName = 'Lumi';

const messages = ref<ChatMessage[]>([
    {
        id: 1,
        role: 'bot',
        text: `Oi! Eu sou a ${agentName}, sua agente virtual. Posso ajudar com planos, limites, exportacoes e onboarding. Pergunte algo rapido.`,
    },
]);

const quickReplyMode = ref<'default' | 'sales'>('default');

const quickReplies = computed(() => {
    if (quickReplyMode.value === 'sales') {
        return ['Email', 'WhatsApp', 'Voltar'];
    }

    return [
        'Quais são os planos?',
        'Tem limite de membros?',
        'Como exportar relatorios?',
        'Falar com vendas',
    ];
});

const nextId = computed(() => messages.value.length + 1);

const addMessage = (role: ChatMessage['role'], text: string) => {
    messages.value.push({
        id: nextId.value,
        role,
        text,
    });
};

const respond = (text: string) => {
    const normalized = text.trim().toLowerCase();

    if (normalized.includes('plano')) {
        quickReplyMode.value = 'default';
        addMessage(
            'bot',
            `${agentName}: Temos Free, Pro e Business. O Free começa com limites basicos por workspace.`,
        );
        return;
    }

    if (normalized.includes('membro')) {
        quickReplyMode.value = 'default';
        addMessage(
            'bot',
            `${agentName}: O limite de membros depende do plano. Free tem ate 3, Pro ate 10, Business ate 50.`,
        );
        return;
    }

    if (normalized.includes('export')) {
        quickReplyMode.value = 'default';
        addMessage(
            'bot',
            `${agentName}: Relatorios CSV podem ser exportados pelo board. O Free permite 1 exportacao por mes.`,
        );
        return;
    }

    if (normalized.includes('vendas') || normalized.includes('contato')) {
        quickReplyMode.value = 'sales';
        addMessage(
            'bot',
            `${agentName}: Posso direcionar para contato: email ou WhatsApp. Escolha abaixo.`,
        );
        return;
    }

    quickReplyMode.value = 'default';
    addMessage(
        'bot',
        `${agentName}: Posso ajudar com planos, limites, exportacoes e onboarding. Quer falar com vendas?`,
    );
};

const submit = () => {
    const value = input.value.trim();
    if (!value) {
        return;
    }

    addMessage('user', value);
    input.value = '';
    respond(value);
};

const sendQuickReply = (value: string) => {
    if (quickReplyMode.value === 'sales') {
        if (value === 'Email') {
            addMessage('user', 'Email');
            addMessage('bot', `${agentName}: Envie para contato@trackmytask.com.`);
            return;
        }

        if (value === 'WhatsApp') {
            addMessage('user', 'WhatsApp');
            addMessage(
                'bot',
                `${agentName}: Clique no link de WhatsApp abaixo para iniciar a conversa.`,
            );
            return;
        }

        if (value === 'Voltar') {
            quickReplyMode.value = 'default';
            addMessage('user', 'Voltar');
            addMessage('bot', `${agentName}: Certo! Em que mais posso ajudar?`);
            return;
        }
    }

    addMessage('user', value);
    respond(value);
};
const openSalesFlow = () => {
    isOpen.value = true;
    quickReplyMode.value = 'sales';
    addMessage(
        'bot',
        `${agentName}: Posso direcionar para contato: email ou WhatsApp. Escolha abaixo.`,
    );
};

defineExpose({ openSalesFlow });
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <div v-if="isOpen" class="w-80 rounded-2xl border border-border/70 bg-card shadow-lg">
            <div class="flex items-center justify-between border-b border-border/70 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold">Lumi · Agente virtual</p>
                    <p class="text-xs text-muted-foreground">Respostas rapidas e contato</p>
                </div>
                <Button size="sm" variant="ghost" @click="isOpen = false">
                    Fechar
                </Button>
            </div>
            <div class="max-h-80 space-y-3 overflow-y-auto px-4 py-3 text-sm">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    :class="[
                        'rounded-lg px-3 py-2',
                        message.role === 'bot'
                            ? 'bg-muted/50 text-foreground'
                            : 'bg-primary/10 text-foreground',
                    ]"
                >
                    {{ message.text }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="reply in quickReplies"
                        :key="reply"
                        size="sm"
                        variant="outline"
                        class="text-xs"
                        @click="sendQuickReply(reply)"
                    >
                        {{ reply }}
                    </Button>
                </div>
            </div>
            <div class="border-t border-border/70 px-4 py-3">
                <div class="flex items-center gap-2">
                    <Input
                        v-model="input"
                        placeholder="Digite sua duvida"
                        @keydown.enter.prevent="submit"
                    />
                    <Button size="sm" @click="submit">Enviar</Button>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                    <a
                        class="underline"
                        href="mailto:contato@trackmytask.com"
                        rel="noreferrer"
                    >
                        Email
                    </a>
                    <span>•</span>
                    <a
                        class="underline"
                        href="https://wa.me/5511999999999"
                        target="_blank"
                        rel="noreferrer"
                    >
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <Button
            v-else
            class="h-12 w-12 rounded-full shadow-lg"
            @click="isOpen = true"
        >
            Chat
        </Button>
    </div>
</template>
