import OrderBook from '@/components/OrderBook.vue';
import { useOrdersStore } from '@/stores/orders';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('OrderBook.vue', () => {
    it('fetches the order book on mount for the default symbol', () => {
        mount(OrderBook, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const ordersStore = useOrdersStore();
        expect(ordersStore.fetchOrderBook).toHaveBeenCalledTimes(1);
        expect(ordersStore.fetchOrderBook).toHaveBeenCalledWith('BTC');
    });

    it('shows loading state', async () => {
        const wrapper = mount(OrderBook, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const ordersStore = useOrdersStore();
        ordersStore.loading = true;

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Loading order book...');
    });

    it('fetches the order book when the symbol is changed', async () => {
        const wrapper = mount(OrderBook, {
            global: {
                plugins: [
                    createTestingPinia({
                        createSpy: vi.fn,
                        initialState: {
                            orders: {
                                orderBook: {
                                    buy: [],
                                    sell: [],
                                },
                            },
                        },
                    }),
                ],
            },
        });

        const ordersStore = useOrdersStore();
        // Called once on mount
        expect(ordersStore.fetchOrderBook).toHaveBeenCalledTimes(1);

        // Change the select value
        await wrapper.find('select').setValue('ETH');

        // Should be called again with the new symbol
        expect(ordersStore.fetchOrderBook).toHaveBeenCalledTimes(2); // 1 on mount, 1 on watch
        expect(ordersStore.fetchOrderBook).toHaveBeenLastCalledWith('ETH');
    });
});
