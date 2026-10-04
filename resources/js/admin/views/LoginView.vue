<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { setUser } from '../session';

const router = useRouter();
const form = reactive({ email: '', password: '' });
const errors = ref({});
const pending = ref(false);

async function submit() {
    pending.value = true;
    errors.value = {};

    try {
        const data = await api('/admin/api/login', { method: 'POST', body: { ...form } });
        setUser(data.user);
        await router.push('/admin');
    } catch (error) {
        errors.value = error.errors || { email: [error.message] };
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center px-4 py-10">
        <form class="w-full max-w-md rounded-[1.6rem] border border-cv-line bg-cv-elevated p-6 sm:p-8" @submit.prevent="submit">
            <p class="text-sm tracking-[0.18em] text-cv-accent uppercase">Panel admin</p>
            <h1 class="mt-3 font-display text-4xl">Masuk</h1>
            <p class="mt-2 text-sm text-cv-muted">Kelola isi curriculum vitae dari sini.</p>
            <label class="mt-8 block text-sm" for="email">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
                class="mt-2 w-full rounded-xl border border-cv-line bg-cv-bg px-3 py-3 text-base outline-none focus:border-cv-accent"
            >
            <p v-if="errors.email" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ errors.email[0] }}</p>
            <label class="mt-4 block text-sm" for="password">Kata sandi</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                class="mt-2 w-full rounded-xl border border-cv-line bg-cv-bg px-3 py-3 text-base outline-none focus:border-cv-accent"
            >
            <p v-if="errors.password" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ errors.password[0] }}</p>
            <button
                type="submit"
                class="mt-6 w-full rounded-full bg-cv-ink py-3 text-sm text-cv-bg disabled:opacity-60"
                :disabled="pending"
            >
                {{ pending ? 'Memeriksa…' : 'Masuk' }}
            </button>
        </form>
    </main>
</template>
