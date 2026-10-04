<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { api } from '../api';

const data = ref(null);
const notice = ref('');
const error = ref('');
const pending = ref(false);
const applying = ref(false);
const screen = ref(null);
const stickToEnd = ref(true);
let timer = null;

const job = computed(() => data.value?.job ?? { state: 'idle', steps: [], message: '' });
const busy = computed(() => pending.value || applying.value || job.value.state === 'running');
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
const statusMark = {
    pending: '..',
    running: '>>',
    ok: 'ok',
    failed: '!!',
    skipped: '--',
};

const host = computed(() => {
    try {
        return new URL(data.value?.site || window.location.origin).host;
    } catch {
        return 'server';
    }
});

const prompt = computed(() => `${host.value}:~$`);

const lines = computed(() => {
    const rows = [];
    const write = (kind, text) => rows.push({ kind, text });
    const info = data.value;

    write('prompt', `${prompt.value} status`);
    write('out', `situs        ${info?.site || '…'}`);
    write('out', `repositori   ${info?.repository || '…'}`);
    write('out', `cabang       ${info?.branch || 'main'}`);
    if (info?.commit) {
        write('out', `versi        ${info.commit.short}  ${info.commit.subject}`);
        write('dim', `tanggal      ${formatDate(info.commit.date)}`);
    } else {
        write('dim', 'versi        belum terbaca');
    }
    if (info?.binaries?.php) {
        write('dim', `php          ${info.binaries.php}`);
    }

    if (missing.value.length) {
        write('prompt', `${prompt.value} which ${missing.value.join(' ')}`);
        write('err', `tidak ditemukan: ${missing.value.join(', ')}`);
    }

    if (info?.dirty?.length) {
        write('prompt', `${prompt.value} git status --porcelain`);
        info.dirty.forEach((file) => write('err', ` M ${file}`));
        write('err', 'pembaruan dihentikan supaya perubahan lokal tidak tertimpa');
    }

    if ((info?.ahead ?? 0) > 0) {
        write('err', `server punya ${info.ahead} komit yang belum ada di GitHub`);
    }

    if (info?.pending?.commits?.length) {
        write('prompt', `${prompt.value} git log HEAD..origin/${info.branch || 'main'}`);
        info.pending.commits.forEach((commit) => write('ok', `${commit.hash}  ${commit.subject}`));
    } else if (info?.checked && !info?.error) {
        write('prompt', `${prompt.value} git rev-list --count HEAD..origin/${info.branch || 'main'}`);
        write('ok', '0  sudah sama dengan GitHub');
    }

    if (notice.value) {
        write('ok', notice.value);
    }
    if (error.value) {
        write('err', error.value);
    }
    if (info?.error && info.error !== error.value) {
        write('err', info.error);
    }

    if (job.value.steps?.length) {
        write('prompt', `${prompt.value} app:update`);
        job.value.steps.forEach((step) => {
            const kind = step.status === 'failed' ? 'err' : step.status === 'ok' ? 'ok' : 'dim';
            write(kind, `[${statusMark[step.status] || '  '}] ${step.label}  ${statusLabel[step.status] || step.status}`);
            if (step.output) {
                step.output.split(/\r?\n/).forEach((line) => write('dim', `    ${line}`));
            }
        });
    }

    if (job.value.message && job.value.state !== 'idle') {
        write(job.value.state === 'failed' ? 'err' : 'ok', job.value.message);
    }

    write('dim', 'deploy key diperlukan jika repositori privat. npm tidak dijalankan di server.');

    return rows;
});

function onScreenScroll() {
    const element = screen.value;
    if (!element) {
        return;
    }

    stickToEnd.value = element.scrollHeight - element.scrollTop - element.clientHeight < 48;
}

watch(lines, async () => {
    await nextTick();
    if (stickToEnd.value && screen.value) {
        screen.value.scrollTop = screen.value.scrollHeight;
    }
});

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

    applying.value = true;
    error.value = '';
    notice.value = '';
    watchJob();

    try {
        const nextJob = await api('/admin/api/updates', {
            method: 'POST',
            body: { confirm: true, mode },
        });
        data.value = { ...data.value, job: nextJob };
        if (nextJob.state !== 'running') {
            stop();
            if (nextJob.state === 'failed') {
                error.value = nextJob.message || 'Pembaruan gagal.';
            } else {
                notice.value = nextJob.message || 'Pembaruan selesai.';
            }
        }
    } catch (caught) {
        if ([401, 419, 422].includes(caught.status)) {
            error.value = caught.errors?.update?.[0] || caught.message;
            stop();
        }
    } finally {
        applying.value = false;
    }
}

function watchJob() {
    stop();
    timer = window.setInterval(async () => {
        try {
            const next = await api('/admin/api/updates');
            data.value = next;
            if (next.job?.state === 'success' || next.job?.state === 'failed') {
                stop();
                if (next.job.state === 'failed') {
                    error.value = next.job.message || 'Pembaruan gagal.';
                } else {
                    notice.value = next.job.message || 'Pembaruan selesai.';
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
    if (data.value?.job?.state === 'running') {
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
                {{ applying ? 'Memasang…' : 'Pasang pembaruan' }}
            </button>
            <button type="button" class="rounded-full border border-cv-line px-5 py-3 text-sm disabled:opacity-60" :disabled="busy || blocked" @click="apply('rebuild')">
                {{ applying ? 'Memasang…' : 'Pasang ulang' }}
            </button>
        </div>

        <div class="term mt-6 overflow-hidden rounded-xl border border-black/50 bg-[#101412] shadow-[0_24px_70px_rgba(0,0,0,0.35)]">
            <div class="flex items-center gap-2 border-b border-white/10 bg-[#1a211c] px-4 py-2.5">
                <span class="size-2.5 rounded-full bg-[#ff5f57]"></span>
                <span class="size-2.5 rounded-full bg-[#febc2e]"></span>
                <span class="size-2.5 rounded-full bg-[#28c840]"></span>
                <p class="ml-2 truncate font-mono text-xs text-[#8b978f]">{{ host }} — pembaruan</p>
            </div>
            <div class="relative h-[28rem]">
                <div
                    ref="screen"
                    class="h-full overflow-auto px-4 py-3 font-mono text-[13px] leading-6 text-[#d7e0d8]"
                    @scroll="onScreenScroll"
                >
                    <p v-for="(line, index) in lines" :key="index" class="whitespace-pre-wrap break-words">
                        <span v-if="line.kind === 'prompt'" class="text-[#7dcea0]">{{ line.text }}</span>
                        <span v-else-if="line.kind === 'ok'" class="text-[#9ddead]">{{ line.text }}</span>
                        <span v-else-if="line.kind === 'err'" class="text-[#f07178]">{{ line.text }}</span>
                        <span v-else-if="line.kind === 'dim'" class="text-[#8b978f]">{{ line.text }}</span>
                        <span v-else>{{ line.text }}</span>
                    </p>
                    <p class="flex items-center">
                        <span class="text-[#7dcea0]">{{ prompt }}</span>
                        <span class="term-cursor ml-2 inline-block h-4 w-2 bg-[#d7e0d8]"></span>
                    </p>
                </div>
                <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.035)_1px,transparent_1px)] bg-[length:100%_4px]"></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.term-cursor {
    animation: term-blink 1.05s steps(1) infinite;
}

@keyframes term-blink {
    50% {
        opacity: 0;
    }
}
</style>
