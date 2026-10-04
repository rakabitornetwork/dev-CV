<script setup>
import { computed, reactive, ref, watch } from 'vue';
import Icon from '../components/Icon.vue';
import { iconOptions, resources } from '../catalog';
import { api } from '../api';

const props = defineProps({
    kind: { type: String, required: true },
});

const config = computed(() => resources[props.kind]);
const rows = ref([]);
const open = ref(false);
const editing = ref(null);
const errors = ref({});
const notice = ref('');
const pending = ref(false);
const form = reactive({});
const files = ref({});

function blank() {
    const data = { is_visible: true };
    for (const field of config.value.fields) {
        data[field.key] = field.type === 'checkbox' ? false : '';
    }
    return data;
}

function resetForm(row = null) {
    const data = blank();
    if (row) {
        for (const field of config.value.fields) {
            if (field.key === 'tech_stack' && Array.isArray(row.tech_stack)) {
                data.tech_stack = row.tech_stack.join(', ');
            } else if (field.type === 'checkbox') {
                data[field.key] = Boolean(row[field.key]);
            } else if (field.type !== 'file') {
                data[field.key] = row[field.key] ?? '';
            }
        }
        data.is_visible = Boolean(row.is_visible);
    }
    Object.keys(form).forEach((key) => delete form[key]);
    Object.assign(form, data);
    files.value = {};
    errors.value = {};
}

async function load() {
    rows.value = await api(`/admin/api/content/${props.kind}`);
}

watch(() => props.kind, () => {
    open.value = false;
    notice.value = '';
    load();
}, { immediate: true });

function startCreate() {
    editing.value = null;
    resetForm();
    open.value = true;
}

function startEdit(row) {
    editing.value = row;
    resetForm(row);
    open.value = true;
}

function payload() {
    const hasFile = config.value.fields.some((field) => field.type === 'file');
    if (!hasFile) {
        const body = { ...form };
        if (props.kind === 'projects') {
            body.tech_stack = String(form.tech_stack || '').split(',').map((item) => item.trim()).filter(Boolean);
        }
        return { body };
    }

    const formData = new FormData();
    for (const field of config.value.fields) {
        if (field.type === 'file') {
            if (files.value[field.key]) {
                formData.append(field.key, files.value[field.key]);
            }
            continue;
        }
        if (field.type === 'checkbox') {
            formData.append(field.key, form[field.key] ? '1' : '0');
            continue;
        }
        if (form[field.key] !== '' && form[field.key] != null) {
            formData.append(field.key, form[field.key]);
        }
    }
    formData.append('is_visible', form.is_visible ? '1' : '0');

    return { form: formData };
}

async function save() {
    pending.value = true;
    errors.value = {};
    const url = editing.value
        ? `/admin/api/content/${props.kind}/${editing.value.id}`
        : `/admin/api/content/${props.kind}`;

    try {
        await api(url, { method: 'POST', ...payload() });
        open.value = false;
        notice.value = 'Tersimpan.';
        await load();
    } catch (error) {
        errors.value = error.errors || {};
        if (!error.errors) {
            notice.value = error.message;
        }
    } finally {
        pending.value = false;
    }
}

async function move(row, direction) {
    rows.value = await api(`/admin/api/content/${props.kind}/${row.id}/move`, {
        method: 'POST',
        body: { direction },
    });
}

async function toggle(row) {
    const updated = await api(`/admin/api/content/${props.kind}/${row.id}/visibility`, {
        method: 'POST',
        body: { is_visible: !row.is_visible },
    });
    Object.assign(row, updated);
}

async function remove(row) {
    if (!window.confirm(`Hapus ${config.value.singular} ini?`)) {
        return;
    }
    await api(`/admin/api/content/${props.kind}/${row.id}`, { method: 'DELETE' });
    notice.value = 'Dihapus.';
    await load();
}

function preview(row) {
    if (row.cover_url) {
        return row.cover_url;
    }
    if (row.avatar_url) {
        return row.avatar_url;
    }
    return '';
}
</script>

<template>
    <section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-4xl">{{ config.title }}</h1>
                <p class="mt-2 text-sm text-cv-muted">Ubah urutan, sembunyikan, atau sunting tiap entri.</p>
            </div>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-full bg-cv-ink px-4 py-2.5 text-sm text-cv-bg" @click="startCreate">
                <Icon name="Plus" />
                Tambah
            </button>
        </div>
        <p v-if="notice" class="mt-4 text-sm text-cv-accent">{{ notice }}</p>
        <p v-if="rows.length === 0" class="mt-6 rounded-2xl border border-dashed border-cv-line px-4 py-8 text-cv-muted">
            Belum ada {{ config.singular }}.
        </p>
        <div class="mt-6 space-y-3">
            <article v-for="(row, index) in rows" :key="row.id" class="rounded-2xl border border-cv-line bg-cv-elevated p-4">
                <div class="flex min-w-0 items-start gap-3">
                    <img v-if="preview(row)" :src="preview(row)" alt="" class="size-14 shrink-0 rounded-xl object-cover">
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-medium">{{ config.heading(row) }}</h2>
                        <p class="truncate text-sm text-cv-muted">{{ config.meta(row) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border border-cv-line px-3 py-1.5 text-sm" @click="startEdit(row)">Sunting</button>
                    <button type="button" class="inline-flex items-center gap-1 rounded-full border border-cv-line px-3 py-1.5 text-sm" @click="toggle(row)">
                        <Icon :name="row.is_visible ? 'Eye' : 'EyeOff'" />
                        {{ row.is_visible ? 'Tampil' : 'Tersembunyi' }}
                    </button>
                    <button type="button" class="rounded-full border border-cv-line p-2" :disabled="index === 0" aria-label="Naikkan" @click="move(row, 'up')">
                        <Icon name="ChevronUp" />
                    </button>
                    <button type="button" class="rounded-full border border-cv-line p-2" :disabled="index === rows.length - 1" aria-label="Turunkan" @click="move(row, 'down')">
                        <Icon name="ChevronDown" />
                    </button>
                    <button type="button" class="rounded-full border border-cv-line p-2" aria-label="Hapus" @click="remove(row)">
                        <Icon name="Trash2" />
                    </button>
                </div>
            </article>
        </div>

        <div v-if="open" class="fixed inset-0 z-50 grid items-end bg-black/50 sm:place-items-center sm:p-6" @click.self="open = false">
            <form class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-cv-elevated p-5 sm:max-w-lg sm:rounded-3xl" @submit.prevent="save">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-2xl">{{ editing ? 'Sunting' : 'Tambah' }} {{ config.singular }}</h2>
                    <button type="button" class="text-sm text-cv-muted" @click="open = false">Tutup</button>
                </div>
                <div class="mt-5 grid gap-4">
                    <label v-for="field in config.fields" :key="field.key" class="block text-sm">
                        <span>{{ field.label }}</span>
                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="form[field.key]"
                            rows="4"
                            class="mt-2 w-full rounded-xl border border-cv-line bg-cv-bg px-3 py-3 text-base outline-none focus:border-cv-accent"
                        />
                        <select
                            v-else-if="field.type === 'icon'"
                            v-model="form[field.key]"
                            class="mt-2 w-full rounded-xl border border-cv-line bg-cv-bg px-3 py-3 text-base"
                        >
                            <option value="">Pilih ikon</option>
                            <option v-for="option in iconOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <input
                            v-else-if="field.type === 'file'"
                            type="file"
                            :accept="field.accept"
                            class="mt-2 block w-full text-sm"
                            @change="files[field.key] = $event.target.files?.[0] || null"
                        >
                        <label v-else-if="field.type === 'checkbox'" class="mt-2 flex items-center gap-2">
                            <input v-model="form[field.key]" type="checkbox">
                            <span>Ya</span>
                        </label>
                        <input
                            v-else
                            v-model="form[field.key]"
                            :type="field.type === 'number' ? 'number' : field.type === 'month' ? 'month' : 'text'"
                            class="mt-2 w-full rounded-xl border border-cv-line bg-cv-bg px-3 py-3 text-base outline-none focus:border-cv-accent"
                        >
                        <span v-if="errors[field.key]" class="mt-1 block text-red-700 dark:text-red-300">{{ errors[field.key][0] }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_visible" type="checkbox">
                        Tampil di halaman CV
                    </label>
                </div>
                <button type="submit" class="mt-6 w-full rounded-full bg-cv-ink py-3 text-sm text-cv-bg" :disabled="pending">
                    {{ pending ? 'Menyimpan…' : 'Simpan' }}
                </button>
            </form>
        </div>
    </section>
</template>
