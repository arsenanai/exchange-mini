import WalletOverview from '@/components/WalletOverview.vue';
import { useProfileStore } from '@/stores/profile';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('WalletOverview.vue', () => {
    it('shows loading state and calls fetchProfile on mount', () => {
        const wrapper = mount(WalletOverview, {
            global: {
                plugins: [
                    createTestingPinia({
                        createSpy: vi.fn,
                    }),
                ],
            },
        });

        const profileStore = useProfileStore();

        // Initially, the store is not loading, but the component triggers the fetch
        expect(profileStore.fetchProfile).toHaveBeenCalledTimes(1);

        // Manually set loading to true to test the template
        profileStore.loading = true;
        // We need to `await` the next tick for Vue to update the DOM
        wrapper.vm.$nextTick(() => {
            expect(wrapper.text()).toContain('Loading...');
        });
    });

    it('displays an error message if fetching fails', async () => {
        const wrapper = mount(WalletOverview, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const profileStore = useProfileStore();
        profileStore.loading = false;
        profileStore.error = 'Failed to load wallet';

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Failed to load wallet');
    });

    it('displays the user balances correctly', async () => {
        const wrapper = mount(WalletOverview, {
            global: {
                plugins: [
                    createTestingPinia({
                        createSpy: vi.fn,
                        initialState: {
                            profile: {
                                user: {
                                    id: 1,
                                    name: 'Test User',
                                    email: 'test@test.com',
                                    balanceUsd: '9876.54',
                                    assets: [
                                        {
                                            id: 1,
                                            symbol: 'BTC',
                                            amount: '1.5',
                                            lockedAmount: '0.5',
                                        },
                                    ],
                                },
                            },
                        },
                    }),
                ],
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('$9876.54');
        expect(wrapper.text()).toContain('BTC');
        expect(wrapper.text()).toContain('1.5 (0.5 locked)');
    });
});
