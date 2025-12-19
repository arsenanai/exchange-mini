/// <reference types="vitest/globals" />

import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { csrfCookie } from '../bootstrap';
import { useAuthStore } from '../stores/auth';
import type { User } from '../stores/types';

vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        defaults: {
            headers: {
                common: {},
            },
        },
    },
}));

describe('Auth Store', () => {
    beforeEach(() => {
        // creates a fresh pinia and make it active
        // so it's automatically picked up by any useStore() call
        // without having to pass it to it: `useStore(pinia)`
        setActivePinia(createPinia());
        localStorage.clear(); // Clear localStorage before each test
        vi.mock('../bootstrap');
    });

    afterEach(() => {
        vi.clearAllMocks();
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

    it('returns the current user via the getter', () => {
        const store = useAuthStore();
        const user: User = {
            id: 1,
            name: 'Test User',
            email: 'test@test.com',
            balanceUsd: '1000',
            assets: [],
        };

        store.user = user;

        expect(store.currentUser).toEqual(user);
    });

    it('calls the csrfCookie endpoint', async () => {
        const store = useAuthStore();
        (csrfCookie as vi.Mock).mockResolvedValue({});

        await store.getCsrfCookie();
        expect(csrfCookie).toHaveBeenCalledTimes(1);
    });

    it('logins in a user, sets token, and navigates', async () => {
        const store = useAuthStore();
        const user: User = {
            id: 1,
            name: 'Test User',
            email: 'test@test.com',
            balanceUsd: '1000',
            assets: [],
        };
        const token = 'fake-token';

        (csrfCookie as vi.Mock).mockResolvedValue({});
        (axios.post as vi.Mock).mockResolvedValue({
            data: { user: { data: user }, token },
        });

        await store.login({ email: 'test@test.com', password: 'password' });

        expect(store.isAuthenticated).toBe(true);
        expect(store.user).toEqual(user);
        expect(store.token).toBe(token);
    });

    it('registers a user and then logs them in', async () => {
        const store = useAuthStore();
        const userInfo = {
            name: 'New User',
            email: 'new@test.com',
            password: 'password',
        };
        const user: User = {
            id: 2,
            name: 'New User',
            email: 'new@test.com',
            balanceUsd: '10000',
            assets: [],
        };
        const token = 'new-fake-token';

        (csrfCookie as vi.Mock).mockResolvedValue({});
        // Mock the register call
        (axios.post as vi.Mock).mockResolvedValueOnce({
            data: { user: { data: user } },
        });
        // Mock the subsequent login call
        (axios.post as vi.Mock).mockResolvedValueOnce({
            data: { user: { data: user }, token },
        });

        await store.register(userInfo);

        expect(axios.post).toHaveBeenCalledWith('/register', userInfo);
        expect(axios.post).toHaveBeenCalledWith('/login', {
            email: userInfo.email,
            password: userInfo.password,
        });
        expect(store.isAuthenticated).toBe(true);
        expect(store.user).toEqual(user);
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

        (axios.post as vi.Mock).mockResolvedValue({});

        await store.logout();

        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
    });

    it('clears auth data on logout even if API call fails', async () => {
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

        // Mock a failed logout API call
        (axios.post as vi.Mock).mockRejectedValue(new Error('Network Error'));

        // Spy on console.error to suppress the expected error message
        const consoleErrorSpy = vi
            .spyOn(console, 'error')
            .mockImplementation(() => {});

        await store.logout();

        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
        expect(store.token).toBeNull();

        // Restore the original console.error function
        consoleErrorSpy.mockRestore();
    });
});
