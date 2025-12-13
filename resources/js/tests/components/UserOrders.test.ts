import UserOrders from '@/components/UserOrders.vue';
import { useOrdersStore } from '@/stores/orders';
import type { Order } from '@/stores/types';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const mockOrders: Order[] = [
    {
        id: 1,
        userId: 1,
        symbol: 'BTC',
        side: 'buy',
        price: '20000',
        amount: '0.1',
        status: 1, // Open
        lockedUsd: '2000',
        lockedAsset: '0',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    },
    {
        id: 2,
        userId: 1,
        symbol: 'ETH',
        side: 'sell',
        price: '3000',
        amount: '1.0',
        status: 2, // Filled
        lockedUsd: '0',
        lockedAsset: '1.0',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    },
    {
        id: 3,
        userId: 1,
        symbol: 'BTC',
        side: 'buy',
        price: '21000',
        amount: '0.2',
        status: 3, // Cancelled
        lockedUsd: '0',
        lockedAsset: '0',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    },
];

describe('UserOrders.vue', () => {
    it('renders orders and their statuses correctly', async () => {
        const wrapper = mount(UserOrders, {
            global: {
                plugins: [
                    createTestingPinia({
                        createSpy: vi.fn,
                        initialState: {
                            orders: { userOrders: mockOrders },
                        },
                    }),
                ],
            },
        });

        await wrapper.vm.$nextTick();

        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(3);

        // Check status text and cancel button visibility
        const firstRow = wrapper.find('[data-testid="order-row-1"]');
        expect(firstRow.text()).toContain('Open');
        // Check for comma and decimal formatting on the price
        expect(firstRow.text()).toContain('$20,000.00');
        expect(firstRow.find('button').exists()).toBe(true); // Cancel button should be visible

        const secondRow = wrapper.find('[data-testid="order-row-2"]');
        expect(secondRow.text()).toContain('Filled');
        expect(secondRow.find('button').exists()).toBe(false); // No cancel button

        const thirdRow = wrapper.find('[data-testid="order-row-3"]');
        expect(thirdRow.text()).toContain('Cancelled');
        expect(thirdRow.find('button').exists()).toBe(false); // No cancel button
    });

    it('shows loading and empty states correctly', async () => {
        const wrapper = mount(UserOrders, {
            global: {
                plugins: [
                    createTestingPinia({
                        createSpy: vi.fn,
                        initialState: {
                            orders: { userOrders: [] }, // Start with no orders
                        },
                    }),
                ],
            },
        });

        const ordersStore = useOrdersStore();

        // Test loading state
        ordersStore.loading = true;
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Loading orders...');

        // Test empty state
        ordersStore.loading = false;
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('You have no orders.');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('calls cancelOrder when the cancel button is clicked', async () => {
        const wrapper = mount(UserOrders, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });
        const ordersStore = useOrdersStore();
        ordersStore.userOrders = [mockOrders[0]]; // Only the open order
        await wrapper.vm.$nextTick();

        await wrapper
            .find('[data-testid="order-row-1"] button')
            .trigger('click');

        expect(ordersStore.cancelOrder).toHaveBeenCalledTimes(1);
        expect(ordersStore.cancelOrder).toHaveBeenCalledWith(1);
    });
});
