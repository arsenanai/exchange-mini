import Exchange from '@/views/Exchange.vue';
import Login from '@/views/Login.vue';
import Register from '@/views/Register.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import Home from '@/views/Home.vue';

const routes = [
    {
        path: '/',
        name: 'Home',
        component: Home,
    },
    {
        path: '/exchange',
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
    } else if (
        (to.name === 'Login' || to.name === 'Register' || to.name === 'Home') &&
        authStore.isAuthenticated
    ) {
        next('/exchange');
    } else {
        next();
    }
});

export default router;
