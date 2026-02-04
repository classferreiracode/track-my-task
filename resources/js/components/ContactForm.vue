<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAlerts } from '@/composables/useAlerts';

type Props = {
    submitLabel?: string;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    (event: 'submitted'): void;
}>();

const { toast } = useAlerts();

const name = ref('');
const email = ref('');
const company = ref('');
const teamSize = ref('');
const message = ref('');

const submit = async () => {
    if (!name.value || !email.value) {
        await toast({
            icon: 'error',
            title: 'Preencha nome e e-mail para continuar.',
        });
        return;
    }

    await toast({
        icon: 'success',
        title: 'Recebemos seu contato. Vamos responder em breve.',
    });

    name.value = '';
    email.value = '';
    company.value = '';
    teamSize.value = '';
    message.value = '';

    emit('submitted');
};
</script>

<template>
    <form class="grid gap-3" @submit.prevent="submit">
        <div class="grid gap-2 sm:grid-cols-2">
            <Input v-model="name" name="name" placeholder="Nome" required />
            <Input v-model="email" name="email" placeholder="E-mail" type="email" required />
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
            <Input v-model="company" name="company" placeholder="Empresa" />
            <Input v-model="teamSize" name="team_size" placeholder="Tamanho da equipe" />
        </div>
        <textarea
            v-model="message"
            name="message"
            rows="4"
            placeholder="Conte um pouco sobre seu time e necessidades."
            class="border-input flex min-h-24 w-full rounded-md border bg-card px-3 py-2 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
        ></textarea>
        <Button type="submit">
            {{ props.submitLabel ?? 'Enviar contato' }}
        </Button>
    </form>
</template>
