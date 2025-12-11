import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useOrdersStore } from '../stores/orders';
import { useProfileStore } from '../stores/profile';
import type { NewOrder, Order } from '../stores/types';

vi.mock('axios');

const mockOrder: Order = {
    id: 1,
    userId: 1,
    symbol: 'BTC',
    side: 'buy',
    price: '50000',
    amount: '0.1',
    status: 1,
    lockedUsd: '5000',
    lockedAsset: '0',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
};

describe('Orders Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('fetches user orders successfully', async () => {
        const store = useOrdersStore();
        (axios.get as any).mockResolvedValue({ data: { data: [mockOrder] } });

        await store.fetchUserOrders();

        expect(store.userOrders).toEqual([mockOrder]);
    });

    it('fetches order book successfully', async () => {
        const store = useOrdersStore();
        const sellOrder = { ...mockOrder, id: 2, side: 'sell' };
        (axios.get as any).mockResolvedValue({
            data: { data: [mockOrder, sellOrder] },
        });

        await store.fetchOrderBook('BTC');

        expect(store.orderBook.buy).toEqual([mockOrder]);
        expect(store.orderBook.sell).toEqual([sellOrder]);
    });

    it('creates an order successfully', async () => {
        const ordersStore = useOrdersStore();
        const profileStore = useProfileStore();
        const newOrder: NewOrder = {
            symbol: 'BTC',
            side: 'buy',
            price: '50000',
            amount: '0.1',
        };

        (axios.post as any).mockResolvedValue({ data: { order: mockOrder } });
        const fetchProfileSpy = vi
            .spyOn(profileStore, 'fetchProfile')
            .mockResolvedValue();

        await ordersStore.createOrder(newOrder);

        expect(axios.post).toHaveBeenCalledWith('/orders', newOrder);
        expect(ordersStore.userOrders).toContainEqual(mockOrder);
        expect(fetchProfileSpy).toHaveBeenCalled();
    });

    it('handles error when creating an order fails', async () => {
        const store = useOrdersStore();
        const newOrder: NewOrder = {
            symbol: 'BTC',
            side: 'buy',
            price: '50000',
            amount: '0.1',
        };
        (axios.post as any).mockRejectedValue({
            response: { data: { message: 'Insufficient funds' } },
        });

        await expect(store.createOrder(newOrder)).rejects.toThrow();
        expect(store.error).toBe('Insufficient funds');
    });

    it('cancels an order successfully', async () => {
        const ordersStore = useOrdersStore();
        const profileStore = useProfileStore();
        ordersStore.userOrders = [mockOrder];
        const cancelledOrder = { ...mockOrder, status: 3 };

        (axios.post as any).mockResolvedValue({ data: cancelledOrder });
        const fetchProfileSpy = vi
            .spyOn(profileStore, 'fetchProfile')
            .mockResolvedValue();

        await ordersStore.cancelOrder(1);

        expect(axios.post).toHaveBeenCalledWith('/orders/1/cancel');
        expect(ordersStore.userOrders[0].status).toBe(3);
        expect(fetchProfileSpy).toHaveBeenCalled();
    });

    it('handles error when canceling an order fails', async () => {
        const store = useOrdersStore();
        (axios.post as any).mockRejectedValue({
            response: { data: { message: 'Order not open' } },
        });

        await expect(store.cancelOrder(1)).rejects.toThrow();
        expect(store.error).toBe('Order not open');
    });
});
