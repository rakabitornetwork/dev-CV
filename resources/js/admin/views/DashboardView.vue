<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '../api';

const data = ref(null);
const cards = [
    { key: 'experiences', label: 'Pengalaman', to: '/admin/experiences' },
    { key: 'skills', label: 'Keahlian', to: '/admin/skills' },
    { key: 'projects', label: 'Proyek', to: '/admin/projects' },
    { key: 'testimonials', label: 'Testimoni', to: '/admin/testimonials' },
];

onMounted(async () => {
    data.value = await api('/admin/api/dashboard');
});
</script>

<template>
    <section>
        <p class="text-sm text-cv-muted">Ringkasan</p>
        <h1 class="mt-1 font-display text-4xl">{{ data?.profile?.name || 'Curriculum vitae' }}</h1>
        <p v-if="data?.profile?.headline" class="mt-2 max-w-2xl text-cv-muted">{{ data.profile.headline }}</p>
        <div class="mt-8 grid gap-3 sm:grid-cols-2">
            <RouterLink
                v-for="card in cards"
                :key="card.key"
                :to="card.to"
                class="rounded-2xl border border-cv-line bg-cv-elevated p-5"
            >
                <p class="text-sm text-cv-muted">{{ card.label }}</p>
                <p class="mt-2 font-display text-4xl">{{ data?.counts?.[card.key] ?? '—' }}</p>
            </RouterLink>
        </div>
    </section>
</template>
