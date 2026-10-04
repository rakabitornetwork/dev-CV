<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { api } from '../api';

const data = ref(null);
const notice = ref('');
const error = ref('');
const pending = ref(false);
let timer = null;

const job = computed(() => data.value?.job ?? { state: 'idle', steps: [], message: '' });
const busy = computed(() => pending.value || ['queued', 'running'].includes(job.value.state));
const missing = computed(() => {
    const binaries = data.value?.binaries ?? {};

    return ['git', 'composer'].filter((name) => binaries[name] === false);
});
const blocked = computed(() => missing.value.length > 0 || (data.value?.dirty?.length ?? 0) > 0 || (data.value?.ahead ?? 0) > 0);
const statusLabel = {
    pending: 'Menunggu',
    running: 'Berjalan',
    ok: 'Selesai',
    failed: 'Gagal',
    skipped: 'Dilewati',
};

function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

async function load() {
    data.value = await api('/admin/api/updates');
}

async function check() {
    pending.value = true;
    error.value = '';
    notice.value = '';

    try {
        data.value = await api('/admin/api/updates/check', { method: 'POST' });
        if (data.value.error) {
            error.value = data.value.error;
        } else if ((data.value.pending?.count ?? 0) > 0) {
            notice.value = `${data.value.pending.count} pembaruan siap dipasang.`;
        } else {
            notice.value = 'Sudah sama dengan GitHub.';
        }
    } catch (caught) {
        error.value = caught.message;
    } finally {
        pending.value = false;
    }
}

async function apply(mode) {
    const question = mode === 'rebuild'
        ? 'Pasang ulang dependensi PHP di server ini? Situs masuk mode pemeliharaan sebentar.'
        : 'Pasang pembaruan dari GitHub di server ini? Situs masuk mode pemeliharaan sebentar.';

    if (!window.confirm(question)) {
        return;
    }

    pending.value = true;
    error.value = '';
    notice.value = '';

    try {
        const nextJob = await api('/admin/api/updates', {
            method: 'POST',
            body: { confirm: true, mode },
        });
        data.value = { ...data.value, job: nextJob };
        watchJob();
    } catch (caught) {
        error.value = caught.errors?.update?.[0] || caught.message;
    } finally {
        pending.value = false;
    }
}

function watchJob() {
    stop();
    timer = window.setInterval(async () => {
        try {
            const next = await api('/admin/api/updates');
            data.value = next;
            if (!['queued', 'running'].includes(next.job?.state)) {
                stop();
                if (next.job?.state === 'failed') {
                    error.value = next.job.message || 'Pembaruan gagal.';
                } else {
                    notice.value = next.job?.message || 'Pembaruan selesai.';
                }
            }
        } catch (caught) {
            error.value = caught.message;
            stop();
        }
    }, 2000);
}

function stop() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

onMounted(async () => {
    await load();
    if (['queued', 'running'].includes(data.value?.job?.state)) {
        watchJob();
    }
});

onUnmounted(stop);
</script>

<template>
    <section>
        <p class="text-sm text-cv-muted">Server</p>
        <h1 class="mt-1 font-display text-4xl">Pembaruan</h1>
        <p class="mt-2 max-w-2xl text-sm text-cv-muted">
            Tombol di halaman ini memperbarui salinan yang sedang dibuka, yaitu {{ data?.site || 'situs ini' }}.
            Tampilan sudah dibangun sebelum di-push, jadi server tidak menjalankan npm. Berkas .env, database, dan unggahan tidak ikut tertimpa.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <article class="rounded-2xl border border-cv-line bg-cv-elevated p-5">
                <p class="text-sm text-cv-muted">Repositori</p>
                <p class="mt-2 font-display text-2xl">{{ data?.repository || '—' }}</p>
                <p class="mt-1 text-sm text-cv-muted">Cabang {{ data?.branch || 'main' }}</p>
            </article>
            <article class="rounded-2xl border border-cv-line bg-cv-elevated p-5">
                <p class="text-sm text-cv-muted">Versi terpasang</p>
                <p class="mt-2 font-display text-2xl">{{ data?.commit?.short || '—' }}</p>
                <p class="mt-1 text-sm text-cv-muted">{{ data?.commit?.subject }}</p>
                <p v-if="data?.commit?.date" class="mt-1 text-sm text-cv-muted">{{ formatDate(data.commit.date) }}</p>
            </article>
        </div>

        <p v-if="missing.length" class="mt-4 text-sm text-red-700 dark:text-red-300">
            PHP di server ini belum bisa menjalankan: {{ missing.join(', ') }}.
        </p>
        <p v-if="data?.dirty?.length" class="mt-4 text-sm text-red-700 dark:text-red-300">
            Ada perubahan lokal yang belum di-commit: {{ data.dirty.join(', ') }}. Pembaruan dihentikan supaya perubahan itu tidak tertimpa.
        </p>
        <p v-if="(data?.ahead ?? 0) > 0" class="mt-4 text-sm text-red-700 dark:text-red-300">
            Server punya {{ data.ahead }} komit yang belum ada di GitHub. Pembaruan otomatis tidak dijalankan.
        </p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button type="button" class="rounded-full bg-cv-ink px-5 py-3 text-sm text-cv-bg disabled:opacity-60" :disabled="busy" @click="check">
                {{ pending ? 'Mengecek…' : 'Cek GitHub' }}
            </button>
            <button
                type="button"
                class="rounded-full border border-cv-line px-5 py-3 text-sm disabled:opacity-60"
                :disabled="busy || blocked || !data?.pending?.count"
                @click="apply('update')"
            >
                Pasang pembaruan
            </button>
            <button type="button" class="rounded-full border border-cv-line px-5 py-3 text-sm disabled:opacity-60" :disabled="busy || blocked" @click="apply('rebuild')">
                Pasang ulang
            </button>
        </div>

        <p v-if="notice" class="mt-4 text-sm text-cv-accent">{{ notice }}</p>
        <p v-if="error" class="mt-4 whitespace-pre-wrap text-sm text-red-700 dark:text-red-300">{{ error }}</p>

        <ul v-if="data?.pending?.commits?.length" class="mt-6 divide-y divide-cv-line rounded-2xl border border-cv-line">
            <li v-for="commit in data.pending.commits" :key="commit.hash" class="px-4 py-3 text-sm">
                <span class="font-mono text-cv-accent">{{ commit.hash }}</span>
                <span class="ml-3">{{ commit.subject }}</span>
            </li>
        </ul>

        <ol v-if="job.steps?.length" class="mt-6 space-y-3">
            <li v-for="step in job.steps" :key="step.key" class="rounded-2xl border border-cv-line bg-cv-elevated p-4">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span>{{ step.label }}</span>
                    <span class="text-cv-muted">{{ statusLabel[step.status] || step.status }}</span>
                </div>
                <pre v-if="step.output" class="mt-3 max-h-48 overflow-auto whitespace-pre-wrap rounded-xl bg-cv-bg p-3 text-xs text-cv-muted">{{ step.output }}</pre>
            </li>
        </ol>

        <p v-if="job.message && job.state !== 'idle'" class="mt-4 text-sm text-cv-muted">{{ job.message }}</p>
        <p class="mt-8 max-w-2xl text-sm text-cv-muted">
            Server butuh akses baca ke GitHub untuk user yang menjalankan PHP. Jika cek gagal karena autentikasi, pasang deploy key pada user itu.
            Pembaruan menjalankan git pull, composer install, migrasi, lalu menghidupkan situs kembali.
        </p>
    </section>
</template>
