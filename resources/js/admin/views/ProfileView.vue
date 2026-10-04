<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../api';

const form = reactive({
    name: '',
    headline: '',
    summary: '',
    bio: '',
    location: '',
    email: '',
    phone: '',
    availability_label: '',
    seo_title: '',
    seo_description: '',
});
const photoUrl = ref('');
const cvUrl = ref('');
const photo = ref(null);
const cvPdf = ref(null);
const errors = ref({});
const notice = ref('');
const pending = ref(false);

const fields = [
    ['name', 'Nama', 'text'],
    ['headline', 'Headline', 'text'],
    ['summary', 'Ringkasan hero', 'textarea'],
    ['bio', 'Biografi', 'textarea'],
    ['location', 'Lokasi', 'text'],
    ['email', 'Email publik', 'email'],
    ['phone', 'Telepon', 'text'],
    ['availability_label', 'Status ketersediaan', 'text'],
    ['seo_title', 'Judul SEO', 'text'],
    ['seo_description', 'Deskripsi SEO', 'textarea'],
];

onMounted(async () => {
    const profile = await api('/admin/api/profile');
    if (!profile) {
        return;
    }
    for (const [key] of fields) {
        form[key] = profile[key] ?? '';
    }
    photoUrl.value = profile.photo_url || '';
    cvUrl.value = profile.cv_url || '';
});

async function save() {
    pending.value = true;
    errors.value = {};
    notice.value = '';
    const body = new FormData();
    for (const [key] of fields) {
        body.append(key, form[key] ?? '');
    }
    if (photo.value) {
        body.append('photo', photo.value);
    }
    if (cvPdf.value) {
        body.append('cv_pdf', cvPdf.value);
    }

    try {
        const profile = await api('/admin/api/profile', { method: 'POST', form: body });
        photoUrl.value = profile.photo_url || '';
        cvUrl.value = profile.cv_url || '';
        photo.value = null;
        cvPdf.value = null;
        notice.value = 'Profil tersimpan. Muat ulang halaman CV untuk melihatnya.';
    } catch (error) {
        errors.value = error.errors || {};
        notice.value = error.errors ? '' : error.message;
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <section>
        <h1 class="font-display text-4xl">Profil</h1>
        <p class="mt-2 text-sm text-cv-muted">Data ini mengisi hero, tentang, kontak, dan meta halaman.</p>
        <form class="mt-6 grid gap-4" @submit.prevent="save">
            <label v-for="[key, label, type] in fields" :key="key" class="block text-sm">
                <span>{{ label }}</span>
                <textarea
                    v-if="type === 'textarea'"
                    v-model="form[key]"
                    rows="4"
                    class="mt-2 w-full rounded-xl border border-cv-line bg-cv-elevated px-3 py-3 text-base outline-none focus:border-cv-accent"
                />
                <input
                    v-else
                    v-model="form[key]"
                    :type="type"
                    class="mt-2 w-full rounded-xl border border-cv-line bg-cv-elevated px-3 py-3 text-base outline-none focus:border-cv-accent"
                >
                <span v-if="errors[key]" class="mt-1 block text-red-700 dark:text-red-300">{{ errors[key][0] }}</span>
            </label>
            <label class="block text-sm">
                Foto
                <input type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm" @change="photo = $event.target.files?.[0] || null">
                <img v-if="photoUrl" :src="photoUrl" alt="" class="mt-3 h-28 w-24 rounded-xl object-cover">
                <span v-if="errors.photo" class="mt-1 block text-red-700 dark:text-red-300">{{ errors.photo[0] }}</span>
            </label>
            <label class="block text-sm">
                Berkas CV (PDF)
                <input type="file" accept="application/pdf" class="mt-2 block w-full text-sm" @change="cvPdf = $event.target.files?.[0] || null">
                <a v-if="cvUrl" :href="cvUrl" class="mt-2 inline-block text-cv-accent" target="_blank">Lihat PDF saat ini</a>
                <span v-if="errors.cv_pdf" class="mt-1 block text-red-700 dark:text-red-300">{{ errors.cv_pdf[0] }}</span>
            </label>
            <p v-if="notice" class="text-sm text-cv-accent">{{ notice }}</p>
            <button type="submit" class="w-full rounded-full bg-cv-ink py-3 text-sm text-cv-bg sm:w-auto sm:px-6" :disabled="pending">
                {{ pending ? 'Menyimpan…' : 'Simpan profil' }}
            </button>
        </form>
    </section>
</template>
