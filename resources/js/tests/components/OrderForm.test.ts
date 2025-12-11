import OrderForm from '@/components/OrderForm.vue';
import { useOrdersStore } from '@/stores/orders';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { afterEach, beforeEach, describe, expect, it, test, vi } from 'vitest';

describe('OrderForm.vue', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.runOnlyPendingTimers();
        vi.useRealTimers();
    });

    it('submits the form and calls the createOrder action', async () => {
        const wrapper = mount(OrderForm, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const ordersStore = useOrdersStore();

        await wrapper.find('select#symbol').setValue('ETH');
        await wrapper.find('select#side').setValue('sell');
        await wrapper.find('input#price').setValue('3000');
        await wrapper.find('input#amount').setValue('2');

        // Wait for Vue's reactivity to update the v-model bindings
        await wrapper.vm.$nextTick();

        await wrapper.find('form').trigger('submit.prevent');

        expect(ordersStore.createOrder).toHaveBeenCalledTimes(1);
        expect(ordersStore.createOrder).toHaveBeenCalledWith({
            symbol: 'ETH',
            side: 'sell',
            price: '3000',
            amount: '2',
        });
    });

    test('displays a success message and resets the form on successful order creation', async () => {
        const wrapper = mount(OrderForm, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const ordersStore = useOrdersStore();
        // Mock the action to resolve successfully
        (ordersStore.createOrder as any).mockResolvedValue({});

        await wrapper.find('input#price').setValue('50000');
        await wrapper.find('input#amount').setValue('0.5');

        await wrapper.vm.$nextTick();

        await wrapper.find('form').trigger('submit.prevent');

        await flushPromises();

        expect(wrapper.text()).toContain('Order placed successfully!');
        expect(
            (wrapper.find('input#price').element as HTMLInputElement).value,
        ).toBe('');
        expect(
            (wrapper.find('input#amount').element as HTMLInputElement).value,
        ).toBe('');

        // Fast-forward time to check if the success message disappears
        vi.advanceTimersByTime(3000);
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).not.toContain('Order placed successfully!');
    });

    test('displays an error message if order creation fails', async () => {
        const wrapper = mount(OrderForm, {
            global: {
                plugins: [createTestingPinia({ createSpy: vi.fn })],
            },
        });

        const ordersStore = useOrdersStore();
        // Mock the action to reject
        (ordersStore.createOrder as any).mockRejectedValue(
            new Error('API Error'),
        );
        ordersStore.error = 'Insufficient funds';

        await wrapper.find('form').trigger('submit.prevent');

        await flushPromises();

        expect(wrapper.text()).toContain('Insufficient funds');
    });
});
