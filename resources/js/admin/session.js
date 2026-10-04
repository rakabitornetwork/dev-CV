import { ref } from 'vue';
import { api } from './api';

export const currentUser = ref(null);

export async function fetchMe() {
    const data = await api('/admin/api/me');
    currentUser.value = data.user;

    return data.user;
}

export function setUser(user) {
    currentUser.value = user;
}

export function clearUser() {
    currentUser.value = null;
}
