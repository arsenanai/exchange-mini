import { createRouter, createWebHistory } from 'vue-router';
import Exchange from './views/Exchange.vue';
import Login from './views/Login.vue';
import Register from './views/Register.vue';

const routes = [
    {
        path: '/',
        name: 'Exchange',
        component: Exchange,
        meta: { requiresAuth: true }
    },
    {
        path: '/login',
        name: 'Login',
        component: Login
    },
    {
        path: '/register',
        name: 'Register',
        component: Register
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Simple navigation guard (implement proper auth check)
router.beforeEach((to, from, next) => {
    const loggedIn = localStorage.getItem('token');
    if (to.matched.some(record => record.meta.requiresAuth) && !loggedIn) {
        next('/login');
    } else {
        next();
    }
});

export default router;