<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import { navigation } from '../catalog';
import { api } from '../api';
import { clearUser, currentUser } from '../session';

const route = useRoute();
const router = useRouter();
const open = ref(false);

const active = computed(() => (item) => {
    if (item.exact) {
        return route.path === item.to;
    }

    return route.path.startsWith(item.to);
});

async function logout() {
    await api('/admin/api/logout', { method: 'POST' });
    clearUser();
    window.location.href = '/admin/login';
}

function close() {
    open.value = false;
}

router.afterEach(close);
</script>

<template>
    <div class="min-h-screen md:grid md:grid-cols-[16.5rem_1fr]">
        <div v-if="open" class="fixed inset-0 z-30 bg-black/50 md:hidden" @click="close" />
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-cv-line bg-cv-elevated p-4 transition md:static md:w-auto md:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <div class="px-2 py-2">
                <p class="font-display text-lg">Panel CV</p>
                <p class="truncate text-sm text-cv-muted">{{ currentUser?.name }}</p>
            </div>
            <nav class="mt-4 flex flex-1 flex-col gap-1">
                <RouterLink
                    v-for="item in navigation"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm"
                    :class="active(item) ? 'bg-cv-accent-soft text-cv-ink' : 'text-cv-muted hover:bg-cv-bg'"
                >
                    <Icon :name="item.icon" />
                    {{ item.label }}
                </RouterLink>
            </nav>
            <div class="mt-4 flex flex-col gap-2">
                <a href="/" target="_blank" class="rounded-xl px-3 py-2 text-sm text-cv-muted">Lihat halaman CV</a>
                <button type="button" class="flex items-center gap-3 rounded-xl px-3 py-2 text-left text-sm text-red-700 hover:bg-red-700/10 dark:text-red-400" @click="logout">
                    <Icon name="LogOut" />
                    Keluar
                </button>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="flex items-center justify-between border-b border-cv-line px-4 py-3 md:hidden">
                <button type="button" class="rounded-full border border-cv-line px-3 py-2 text-sm" @click="open = true">
                    Menu
                </button>
                <p class="font-display">Panel CV</p>
            </header>
            <main class="mx-auto w-full max-w-5xl px-4 py-6 md:px-8 md:py-8">
                <RouterView />
            </main>
        </div>
    </div>
</template>
