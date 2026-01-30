<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store as onboardingStore } from '@/routes/onboarding';

type Props = {
    errors?: Record<string, string>;
};

defineProps<Props>();
</script>

<template>
    <AuthLayout
        title="Configure seu workspace"
        description="Defina o primeiro workspace e board para começar."
    >
        <Head title="Configurar workspace" />

        <Form v-bind="onboardingStore.form()" class="grid gap-4">
            <div class="grid gap-2">
                <label class="text-sm font-medium">Nome do workspace</label>
                <Input name="workspace_name" placeholder="Ex: Empresa Alpha" required />
                <InputError :message="$page.props.errors?.workspace_name" />
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium">Nome do board</label>
                <Input name="board_name" placeholder="Ex: Projeto Principal" required />
                <InputError :message="$page.props.errors?.board_name" />
            </div>

            <Button type="submit" class="mt-2">Criar ambiente</Button>
        </Form>
    </AuthLayout>
</template>
