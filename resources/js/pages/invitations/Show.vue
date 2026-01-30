<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { accept as invitationAccept } from '@/routes/workspaces/invitations';

type Invitation = {
    email: string;
    role: string;
    expires_at?: string | null;
    accepted_at?: string | null;
    workspace: {
        id: number;
        name: string;
    };
    inviter?: {
        name?: string | null;
        email?: string | null;
    } | null;
};

type Props = {
    status: 'valid' | 'expired' | 'accepted' | 'invalid' | 'mismatch';
    token: string;
    invitation: Invitation | null;
};

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const errors = computed(() => page.props.errors as Record<string, string> | undefined);
const flashInvitation = computed(() => {
    const flash = page.props.flash as { invitation?: string } | undefined;
    return flash?.invitation;
});

const canAccept = computed(
    () =>
        props.status === 'valid' &&
        !!user.value &&
        !!props.invitation &&
        user.value.email === props.invitation.email,
);
</script>

<template>
    <AuthSplitLayout
        title="Convite para o workspace"
        description="Entre ou crie sua conta para participar do workspace."
    >
        <Head title="Convite" />

        <div class="space-y-6">
            <div
                class="rounded-xl border border-border/60 bg-card/80 p-4 text-sm text-foreground"
                v-if="props.invitation"
            >
                <p class="text-xs text-muted-foreground">Workspace</p>
                <p class="text-lg font-semibold">
                    {{ props.invitation.workspace.name }}
                </p>
                <div class="mt-3 grid gap-1 text-xs text-muted-foreground">
                    <span>Convidado: {{ props.invitation.email }}</span>
                    <span>Permissão: {{ props.invitation.role }}</span>
                    <span v-if="props.invitation.inviter?.name">
                        Enviado por: {{ props.invitation.inviter?.name }}
                    </span>
                </div>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed border-border/60 p-4 text-sm text-muted-foreground"
            >
                Convite não encontrado.
            </div>

            <InputError :message="errors?.invitation" />
            <div v-if="flashInvitation" class="text-sm text-rose-600">
                {{ flashInvitation }}
            </div>

            <div v-if="props.status === 'expired'" class="text-sm text-amber-600">
                Este convite expirou. Solicite um novo convite ao administrador.
            </div>
            <div v-else-if="props.status === 'accepted'" class="text-sm text-emerald-600">
                Este convite já foi aceito.
            </div>
            <div v-else-if="props.status === 'mismatch'" class="text-sm text-rose-600">
                Você está logado com um e-mail diferente do convite.
            </div>

            <Form
                v-if="canAccept"
                v-bind="invitationAccept.form(props.token)"
                class="flex flex-col gap-3"
            >
                <Button type="submit">Aceitar convite</Button>
            </Form>

            <div v-else-if="props.status === 'valid'" class="flex flex-col gap-3">
                <Link
                    href="/login"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
                >
                    Entrar para aceitar
                </Link>
                <Link
                    href="/register"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-medium text-slate-900 transition hover:bg-slate-50"
                >
                    Criar conta
                </Link>
            </div>
        </div>
    </AuthSplitLayout>
</template>
