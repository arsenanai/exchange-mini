import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuthStore } from '../stores/auth';
import type { User } from '../stores/types';

describe('Auth Store', () => {
    beforeEach(() => {
        // creates a fresh pinia and make it active
        // so it's automatically picked up by any useStore() call
        // without having to pass it to it: `useStore(pinia)`
        setActivePinia(createPinia());
        localStorage.clear();
        vi.mock('axios');
    });

    it('initializes with no user or token', () => {
        const store = useAuthStore();
        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
        expect(store.isAuthenticated).toBe(false);
    });

    it('sets user and token on setUserAndToken', () => {
        const store = useAuthStore();
        const user: User = {
            id: 1,
            name: 'Test User',
            email: 'test@test.com',
            balanceUsd: '1000',
            assets: [],
        };
        const token = 'fake-token';

        store.setUserAndToken(user, token);

        expect(store.user).toEqual(user);
        expect(store.token).toBe(token);
        expect(store.isAuthenticated).toBe(true);
        expect(localStorage.getItem('user')).toBe(JSON.stringify(user));
        expect(localStorage.getItem('token')).toBe(token);
        expect(axios.defaults.headers.common['Authorization']).toBe(
            `Bearer ${token}`,
        );
    });

    it('clears auth data on logout', async () => {
        const store = useAuthStore();
        // Set up a logged-in state
        const user: User = {
            id: 1,
            name: 'Test User',
            email: 'test@test.com',
            balanceUsd: '1000',
            assets: [],
        };
        const token = 'fake-token';
        store.setUserAndToken(user, token);
        expect(store.isAuthenticated).toBe(true);

        (axios.post as any).mockResolvedValue({});

        await store.logout();

        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
    });
});
