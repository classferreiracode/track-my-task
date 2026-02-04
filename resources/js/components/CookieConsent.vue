<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { privacy, terms } from '@/routes';

const isVisible = ref(false);
const consent = ref<'accepted' | 'declined' | null>(null);

const readConsent = () => {
    const value = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('cookie_consent='))
        ?.split('=')[1];

    if (value === 'accepted' || value === 'declined') {
        consent.value = value;
        return value;
    }

    return null;
};

const setConsent = (value: 'accepted' | 'declined') => {
    document.cookie = `cookie_consent=${value}; path=/; max-age=31536000; samesite=lax`;
    consent.value = value;
    isVisible.value = false;
};

onMounted(() => {
    const existing = readConsent();
    isVisible.value = !existing;
});
</script>

<template>
    <div
        v-if="isVisible"
        class="fixed bottom-6 right-6 z-50 w-[320px] rounded-2xl border border-border/70 bg-card/95 p-4 text-sm shadow-lg backdrop-blur"
    >
        <p class="font-semibold">Cookies e privacidade</p>
        <p class="mt-2 text-xs text-muted-foreground">
            Usamos cookies essenciais e analiticos para melhorar sua experiencia. Ao continuar, voce concorda com
            nossa
            <Link :href="privacy()" class="underline underline-offset-4">Politica de Privacidade</Link>
            e
            <Link :href="terms()" class="underline underline-offset-4">Termos de Uso</Link>.
        </p>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <Button size="sm" @click="setConsent('accepted')">
                Aceitar
            </Button>
            <Button size="sm" variant="outline" @click="setConsent('declined')">
                Recusar
            </Button>
        </div>
    </div>
</template>
