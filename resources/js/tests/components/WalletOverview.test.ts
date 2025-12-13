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

    it.each([
        { input: '10000', expected: '$10,000.00' },
        { input: '9876.54', expected: '$9,876.54' },
        { input: '123.45', expected: '$123.45' },
        { input: '0', expected: '$0.00' },
        { input: '1234567.89', expected: '$1,234,567.89' },
        { input: null, expected: '$0.00' },
        { input: undefined, expected: '$0.00' },
        { input: 'invalid-string', expected: '$0.00' },
    ])(
        'formats balance of "$input" as "$expected"',
        async ({ input, expected }) => {
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
                                        balanceUsd: input,
                                        assets: [],
                                    },
                                },
                            },
                        }),
                    ],
                },
            });

            await wrapper.vm.$nextTick();

            const balanceElement = wrapper.find(
                '[data-testid="wallet-usd-balance"]',
            );
            expect(balanceElement.text()).toBe(expected);
        },
    );

    it('displays asset balances correctly', async () => {
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
                                    balanceUsd: '1000',
                                    assets: [
                                        {
                                            id: 1,
                                            symbol: 'BTC',
                                            amount: '1.50000000',
                                            lockedAmount: '0.50000000',
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

        const assetList = wrapper.find('ul');
        expect(assetList.text()).toContain('BTC');
        expect(assetList.text()).toContain('1.50000000 (0.50000000 locked)');
    });
});
