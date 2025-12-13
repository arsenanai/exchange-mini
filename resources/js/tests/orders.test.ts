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

    it('handles error when fetching user orders fails', async () => {
        const store = useOrdersStore();
        (axios.get as any).mockRejectedValue({
            response: { data: { message: 'Server Error' } },
        });

        await store.fetchUserOrders();

        expect(store.error).toBe('Server Error');
    });

    it('handles generic error when fetching user orders', async () => {
        const store = useOrdersStore();
        // Mock a generic error without a response object
        (axios.get as any).mockRejectedValue(new Error('Network Error'));

        await store.fetchUserOrders();

        // Should use the fallback error message
        expect(store.error).toBe('Failed to fetch user orders');
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

    it('handles error when fetching order book fails', async () => {
        const store = useOrdersStore();
        (axios.get as any).mockRejectedValue({
            response: { data: { message: 'Server Error' } },
        });

        await store.fetchOrderBook('BTC');

        expect(store.error).toBe('Server Error');
    });

    it('handles generic error when fetching order book', async () => {
        const store = useOrdersStore();
        // Mock a generic error without a response object
        (axios.get as any).mockRejectedValue(new Error('Network Error'));

        await store.fetchOrderBook('BTC');

        // Should use the fallback error message
        expect(store.error).toBe('Failed to fetch order book');
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

    it('handles generic error when creating an order fails', async () => {
        const store = useOrdersStore();
        const newOrder: NewOrder = {
            symbol: 'BTC',
            side: 'buy',
            price: '50000',
            amount: '0.1',
        };
        (axios.post as any).mockRejectedValue(new Error('Network Error'));

        await expect(store.createOrder(newOrder)).rejects.toThrow(
            'Network Error',
        );

        expect(store.error).toBe('Failed to create order');
    });

    it('cancels an order successfully', async () => {
        const ordersStore = useOrdersStore();
        const profileStore = useProfileStore();
        ordersStore.userOrders = [mockOrder];
        const cancelledOrder = { ...mockOrder, status: 3 };

        (axios.post as any).mockResolvedValue({
            data: { data: cancelledOrder },
        });
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

    it('handles generic error when canceling an order fails', async () => {
        const store = useOrdersStore();
        (axios.post as any).mockRejectedValue(new Error('Network Error'));

        await expect(store.cancelOrder(1)).rejects.toThrow('Network Error');

        expect(store.error).toBe('Failed to cancel order');
    });

    it.each([
        { side: 'buy', initialBook: { buy: [mockOrder], sell: [] } },
        {
            side: 'sell',
            initialBook: { buy: [], sell: [{ ...mockOrder, side: 'sell' }] },
        },
    ])(
        'updates state on OrderMatched event for a $side order',
        ({ side, initialBook }) => {
            const ordersStore = useOrdersStore();
            const matchedOrder = { ...mockOrder, side: side as 'buy' | 'sell' };
            ordersStore.userOrders = [{ ...matchedOrder }];
            ordersStore.orderBook = initialBook;

            // This will hold the callback passed to window.Echo.listen
            let eventCallback: (event: any) => void;

            // Mock the Echo chain to capture the callback
            const listenMock = vi.fn((eventName, callback) => {
                eventCallback = callback;
            });
            (window as any).Echo = {
                private: vi.fn().mockReturnThis(),
                listen: listenMock,
            };

            ordersStore.listenForMatches(1);

            expect(listenMock).toHaveBeenCalledWith(
                'OrderMatched',
                expect.any(Function),
            );

            // Simulate receiving a Pusher event
            eventCallback({ order: { ...matchedOrder, status: 2 } }); // Status becomes "filled"

            expect(ordersStore.userOrders[0].status).toBe(2);
            if (side === 'buy') {
                expect(ordersStore.orderBook.buy).toHaveLength(0);
            } else {
                expect(ordersStore.orderBook.sell).toHaveLength(0);
            }
        },
    );
});
