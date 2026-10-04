import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from './layouts/AdminLayout.vue';
import LoginView from './views/LoginView.vue';
import DashboardView from './views/DashboardView.vue';
import ProfileView from './views/ProfileView.vue';
import SectionsView from './views/SectionsView.vue';
import ResourceView from './views/ResourceView.vue';
import { fetchMe, currentUser } from './session';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/admin/login', name: 'login', component: LoginView, meta: { public: true } },
        {
            path: '/admin',
            component: AdminLayout,
            children: [
                { path: '', name: 'dashboard', component: DashboardView },
                { path: 'profile', component: ProfileView },
                { path: 'sections', component: SectionsView },
                { path: 'social-links', component: ResourceView, props: { kind: 'social-links' } },
                { path: 'experiences', component: ResourceView, props: { kind: 'experiences' } },
                { path: 'educations', component: ResourceView, props: { kind: 'educations' } },
                { path: 'skills', component: ResourceView, props: { kind: 'skills' } },
                { path: 'projects', component: ResourceView, props: { kind: 'projects' } },
                { path: 'testimonials', component: ResourceView, props: { kind: 'testimonials' } },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    if (to.meta.public) {
        return true;
    }

    if (currentUser.value) {
        return true;
    }

    try {
        await fetchMe();
        return true;
    } catch (error) {
        if (error.status === 401) {
            return { name: 'login' };
        }

        return true;
    }
});

export default router;
