<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    title: string;
    description: string;
    ctaUrl: string;
    ctaText?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <Alert class="border-amber-300/60 bg-amber-50/80 text-amber-900">
        <AlertTriangle class="size-4 text-amber-600" />
        <AlertTitle class="flex flex-wrap items-center justify-between gap-3">
            <span>{{ props.title }}</span>
            <Button size="sm" variant="outline" class="border-amber-400/70" as-child>
                <Link :href="props.ctaUrl">
                    {{ props.ctaText ?? 'Fazer upgrade' }}
                </Link>
            </Button>
        </AlertTitle>
        <AlertDescription class="text-amber-800">
            {{ props.description }}
        </AlertDescription>
    </Alert>
</template>
