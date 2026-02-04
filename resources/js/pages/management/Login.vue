<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { store as loginStore } from '@/routes/management/login';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(loginStore().url, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Management Login" />
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6">
            <div class="rounded-2xl border border-border/70 bg-card/80 p-6 shadow-sm">
                <h1 class="text-xl font-semibold">Management</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Acesso exclusivo para gestores do sistema.
                </p>

                <form class="mt-6 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-xs font-medium">E-mail</label>
                        <Input v-model="form.email" type="email" required />
                        <p v-if="form.errors.email" class="text-xs text-destructive">
                            {{ form.errors.email }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-xs font-medium">Senha</label>
                        <Input v-model="form.password" type="password" required />
                        <p v-if="form.errors.password" class="text-xs text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>
                    <Button type="submit" :disabled="form.processing">
                        Entrar
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
