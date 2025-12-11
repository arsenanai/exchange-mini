import router from '@/router';
import axios from 'axios';
import { defineStore } from 'pinia';
import { useOrdersStore } from './orders';
import type { AuthState, LoginCredentials, RegisterInfo, User } from './types';

// interface AuthState {
//     user: User | null;
//     token: string | null;
// }

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isAuthenticated: (state: AuthState): boolean => !!state.token,
        currentUser: (state: AuthState): User | null => state.user,
    },

    actions: {
        setUserAndToken(user: User, token: string) {
            this.user = user;
            this.token = token;
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            const ordersStore = useOrdersStore();
            ordersStore.listenForMatches(user.id);
        },

        async login(credentials: LoginCredentials) {
            const response = await axios.post('/login', credentials);
            const { token, user } = response.data;
            this.setUserAndToken(user.data, token);
            await router.push('/');
        },

        async register(userInfo: RegisterInfo) {
            await axios.get('/sanctum/csrf-cookie');
            const response = await axios.post('/register', userInfo);
            const { token, user } = response.data;
            this.setUserAndToken(user.data, token);
            await router.push('/');
        },

        async logout(): Promise<void> {
            try {
                await axios.post('/logout');
            } catch (error) {
                console.error(
                    'Logout failed, clearing client-side session anyway.',
                    error,
                );
            } finally {
                this.clearAuthData();
                await router.push('/login');
            }
        },

        clearAuthData(): void {
            this.user = null;
            this.token = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },
    },
});
