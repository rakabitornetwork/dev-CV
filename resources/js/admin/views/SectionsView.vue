<script setup>
import { onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import { api } from '../api';

const rows = ref([]);
const notice = ref('');

async function load() {
    rows.value = await api('/admin/api/sections');
}

onMounted(load);

async function save(row) {
    notice.value = '';
    try {
        const updated = await api(`/admin/api/sections/${row.id}`, {
            method: 'POST',
            body: { title: row.title, is_visible: row.is_visible },
        });
        Object.assign(row, updated);
        notice.value = 'Bagian diperbarui.';
    } catch (error) {
        notice.value = error.message;
    }
}

async function move(row, direction) {
    rows.value = await api(`/admin/api/sections/${row.id}/move`, {
        method: 'POST',
        body: { direction },
    });
}
</script>

<template>
    <section>
        <h1 class="font-display text-4xl">Bagian halaman</h1>
        <p class="mt-2 text-sm text-cv-muted">Sembunyikan bagian yang tidak ingin tampil di landing.</p>
        <p v-if="notice" class="mt-4 text-sm text-cv-accent">{{ notice }}</p>
        <div class="mt-6 space-y-3">
            <article v-for="(row, index) in rows" :key="row.id" class="rounded-2xl border border-cv-line bg-cv-elevated p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input v-model="row.title" class="min-w-0 flex-1 rounded-xl border border-cv-line bg-cv-bg px-3 py-2.5 text-base" @change="save(row)">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="row.is_visible" type="checkbox" @change="save(row)">
                        Tampil
                    </label>
                    <div class="flex gap-2">
                        <button type="button" class="rounded-full border border-cv-line p-2" :disabled="index === 0" aria-label="Naikkan" @click="move(row, 'up')">
                            <Icon name="ChevronUp" />
                        </button>
                        <button type="button" class="rounded-full border border-cv-line p-2" :disabled="index === rows.length - 1" aria-label="Turunkan" @click="move(row, 'down')">
                            <Icon name="ChevronDown" />
                        </button>
                    </div>
                </div>
                <p class="mt-2 text-xs tracking-wide text-cv-muted uppercase">{{ row.key }}</p>
            </article>
        </div>
    </section>
</template>
