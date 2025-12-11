import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useProfileStore } from '../stores/profile';
import type { User } from '../stores/types';

vi.mock('axios');

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
        (axios.get as any).mockResolvedValue({ data: { data: mockUser } });

        await store.fetchProfile();

        expect(store.loading).toBe(false);
        expect(store.user).toEqual(mockUser);
        expect(store.error).toBeNull();
        expect(store.balances.usd).toBe('10000.00');
        expect(store.balances.assets).toHaveLength(1);
    });

    it('handles error when fetching profile fails', async () => {
        const store = useProfileStore();
        (axios.get as any).mockRejectedValue({
            response: { data: { message: 'Server Error' } },
        });

        await store.fetchProfile();

        expect(store.error).toBe('Server Error');
    });
});
