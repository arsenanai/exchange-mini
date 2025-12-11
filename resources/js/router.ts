import Exchange from '@/views/Exchange.vue';
import Login from '@/views/Login.vue';
import Register from '@/views/Register.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    {
        path: '/',
        name: 'Exchange',
        component: Exchange,
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/register',
        name: 'Register',
        component: Register,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (
        to.matched.some((record) => record.meta.requiresAuth) &&
        !authStore.isAuthenticated
    ) {
        next('/login');
    } else {
        next();
    }
});

export default router;
