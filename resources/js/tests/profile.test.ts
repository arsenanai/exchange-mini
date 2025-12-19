/// <reference types="vitest/globals" />

import type { AxiosRequestConfig } from 'axios';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import { useProfileStore } from '../stores/profile';
import type { User } from '../stores/types';

vi.mock('axios', () => {
    const defaultExport = {
        get: vi.fn(),
        post: vi.fn(),
        defaults: { headers: { common: {} } },
    };
    return {
        default: defaultExport,
        __esModule: true,
        isAxiosError: (
            payload: unknown,
        ): payload is import('axios').AxiosError =>
            payload?.isAxiosError === true,
    };
});

describe('Profile Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('initializes with empty state', () => {
        const store = useProfileStore();
        expect(store.user).toBeNull();
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
    });

    it('returns zero balances when no user is set', () => {
        const store = useProfileStore();
        expect(store.balances.usd).toBe('0.00');
        expect(store.balances.assets).toEqual([]);
    });

    it('fetches profile successfully', async () => {
        const store = useProfileStore();
        const mockUser: User = {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            balanceUsd: '10000.00',
            assets: [
                { id: 1, symbol: 'BTC', amount: '1.5', lockedAmount: '0.5' },
            ],
        };
        (axios.get as Mock).mockResolvedValue({
            data: { data: mockUser },
        });
        await store.fetchProfile();

        expect(store.loading).toBe(false);
        expect(store.user).toEqual(mockUser);
        expect(store.error).toBeNull();
        expect(store.balances.usd).toBe('10000.00');
        expect(store.balances.assets).toHaveLength(1);
    });

    it('handles error when fetching profile fails', async () => {
        const store = useProfileStore();
        const error = {
            isAxiosError: true,
            response: {
                data: { message: 'Server Error' },
                status: 500,
                statusText: 'Internal Server Error',
                headers: {},
                config: {} as AxiosRequestConfig,
            },
        };
        (axios.get as Mock).mockRejectedValue(error);

        await store.fetchProfile();

        expect(store.error).toBe('Server Error');
    });

    it('handles generic error when fetching profile fails', async () => {
        const store = useProfileStore();
        // Mock a generic error without a response object
        (axios.get as Mock).mockRejectedValue(new Error('Network Error'));
        await store.fetchProfile();

        // Should use the fallback error message
        expect(store.error).toBe('Failed to fetch profile');
    });
});
