<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login, privacy, terms } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Crie sua conta"
        description="Dados corporativos para habilitar seu workspace"
    >
        <Head title="Cadastro" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nome completo</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Nome e sobrenome"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email corporativo</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="nome@empresa.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Senha</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Crie uma senha segura"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirme a senha</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Repita a senha"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <Label for="accepted_terms" class="flex items-center space-x-3 text-sm">
                        <Checkbox id="accepted_terms" name="accepted_terms" required />
                        <span>
                            Aceito os
                            <TextLink :href="terms()" class="underline underline-offset-4">
                                Termos de Uso
                            </TextLink>
                        </span>
                    </Label>
                    <InputError :message="errors.accepted_terms" />
                </div>

                <div class="grid gap-2">
                    <Label for="accepted_privacy" class="flex items-center space-x-3 text-sm">
                        <Checkbox id="accepted_privacy" name="accepted_privacy" required />
                        <span>
                            Aceito a
                            <TextLink :href="privacy()" class="underline underline-offset-4">
                                Politica de Privacidade
                            </TextLink>
                        </span>
                    </Label>
                    <InputError :message="errors.accepted_privacy" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="7"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Criar conta
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Já possui acesso?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="8"
                    >Entrar</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
