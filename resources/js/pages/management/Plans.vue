<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import ManagementLayout from '@/layouts/ManagementLayout.vue';
import { update as updatePlan } from '@/routes/management/plans';

type Plan = {
    id: number;
    key: string;
    name: string;
    description?: string | null;
    price_monthly: number;
    limits: Record<string, number | null>;
};

type Props = {
    plans: Plan[];
    limitKeys: string[];
};

const props = defineProps<Props>();

const limitLabels: Record<string, string> = {
    max_members: 'Membros',
    max_boards: 'Boards',
    max_tasks_per_board: 'Tarefas por board',
    max_exports_per_month: 'Exportacoes/mês',
    max_active_timers_per_user: 'Timers ativos por usuario',
};

const labelFor = (key: string) => limitLabels[key] ?? key;

const selectedPlan = ref<Plan | null>(null);
const isModalOpen = ref(false);

const form = useForm({
    price_monthly: 0,
    limits: {} as Record<string, number | null>,
});

const openEdit = (plan: Plan) => {
    selectedPlan.value = plan;
    form.price_monthly = plan.price_monthly;
    form.limits = {
        ...plan.limits,
    };
    isModalOpen.value = true;
};

const submit = () => {
    if (!selectedPlan.value) {
        return;
    }

    form.patch(updatePlan(selectedPlan.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
        },
    });
};
</script>

<template>
    <ManagementLayout title="Planos">
        <Head title="Gestao de Planos" />

        <div class="space-y-6">
            <Heading
                title="Gestao de planos"
                description="Edite precos e limites por workspace."
            />

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="(plan, index) in props.plans"
                    :key="plan.id"
                    class="border-border/70 bg-card/80 shadow-sm"
                >
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>{{ plan.name }}</span>
                            <span class="text-xs font-semibold uppercase text-muted-foreground">
                                {{ plan.key }}
                            </span>
                        </CardTitle>
                        <CardDescription>{{ plan.description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Preco mensal</span>
                            <span class="font-medium">R$ {{ plan.price_monthly }}</span>
                        </div>
                        <div class="grid gap-2 text-xs text-muted-foreground">
                            <div
                                v-for="limitKey in props.limitKeys"
                                :key="`${plan.key}-${limitKey}`"
                                class="flex items-center justify-between"
                            >
                                <span>{{ labelFor(limitKey) }}</span>
                                <span class="text-foreground">
                                    {{ plan.limits[limitKey] ?? 'Ilimitado' }}
                                </span>
                            </div>
                        </div>
                        <Button type="button" variant="outline" @click="openEdit(plan)">
                            Editar
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div class="text-xs text-muted-foreground">
                Valores vazios significam ilimitado.
            </div>
        </div>
    </ManagementLayout>

    <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Editar plano</DialogTitle>
                <DialogDescription>
                    Ajuste preco e limites para o plano selecionado.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <label class="text-xs font-medium">Preco mensal (BRL)</label>
                    <Input v-model.number="form.price_monthly" type="number" min="0" />
                    <p v-if="form.errors.price_monthly" class="text-xs text-destructive">
                        {{ form.errors.price_monthly }}
                    </p>
                </div>

                <div class="grid gap-3">
                    <p class="text-xs font-semibold uppercase text-muted-foreground">
                        Limites
                    </p>
                    <div
                        v-for="limitKey in props.limitKeys"
                        :key="`modal-${limitKey}`"
                        class="grid gap-2"
                    >
                        <label class="text-xs font-medium">
                            {{ labelFor(limitKey) }}
                        </label>
                        <Input
                            v-model.number="form.limits[limitKey]"
                            type="number"
                            min="0"
                            placeholder="Ilimitado"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Salvar
                    </Button>
                    <span
                        v-if="form.recentlySuccessful"
                        class="text-xs text-muted-foreground"
                    >
                        Atualizado.
                    </span>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
