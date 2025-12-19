/// <reference types="vitest/globals" />

import axios, { type AxiosRequestConfig } from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import { useOrdersStore } from '../stores/orders';
import { useProfileStore } from '../stores/profile';
import type { NewOrder, Order } from '../stores/types';

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
        (axios.get as Mock).mockResolvedValue({ data: { data: [mockOrder] } });

        await store.fetchUserOrders();

        expect(store.userOrders).toEqual([mockOrder]);
    });

    it('handles error when fetching user orders fails', async () => {
        const store = useOrdersStore();
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

        await store.fetchUserOrders();

        expect(store.error).toBe('Server Error');
    });

    it('handles generic error when fetching user orders', async () => {
        const store = useOrdersStore();
        // Mock a generic error without a response object
        (axios.get as Mock).mockRejectedValue(new Error('Network Error'));

        await store.fetchUserOrders();

        // Should use the fallback error message
        expect(store.error).toBe('Failed to fetch user orders');
    });

    it('fetches order book successfully', async () => {
        const store = useOrdersStore();
        const sellOrder = { ...mockOrder, id: 2, side: 'sell' };
        (axios.get as Mock).mockResolvedValue({
            data: { data: [mockOrder, sellOrder] },
        });

        await store.fetchOrderBook('BTC');

        expect(store.orderBook.buy).toEqual([mockOrder]);
        expect(store.orderBook.sell).toEqual([sellOrder]);
    });

    it('handles error when fetching order book fails', async () => {
        const store = useOrdersStore();
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

        await store.fetchOrderBook('BTC');

        expect(store.error).toBe('Server Error');
    });

    it('handles generic error when fetching order book', async () => {
        const store = useOrdersStore();
        // Mock a generic error without a response object
        (axios.get as Mock).mockRejectedValue(new Error('Network Error'));

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

        (axios.post as Mock).mockResolvedValue({ data: { data: mockOrder } });
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
        const error = {
            isAxiosError: true,
            response: {
                data: { message: 'Insufficient funds' },
                status: 422,
                statusText: 'Unprocessable Entity',
                headers: {},
                config: {} as AxiosRequestConfig,
            },
        };
        (axios.post as Mock).mockRejectedValue(error);

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
        (axios.post as Mock).mockRejectedValue(new Error('Network Error'));

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

        (axios.post as Mock).mockResolvedValue({
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

    it('handles canceling an order not present in the local list', async () => {
        const ordersStore = useOrdersStore();
        ordersStore.userOrders = [mockOrder]; // Contains order with ID 1
        const originalOrders = [...ordersStore.userOrders];

        // The API successfully cancels order 99, which is not in our local list
        const cancelledOrder = { ...mockOrder, id: 99, status: 3 };
        (axios.post as Mock).mockResolvedValue({
            data: { data: cancelledOrder },
        });

        await ordersStore.cancelOrder(99);

        // The API call should still be made
        expect(axios.post).toHaveBeenCalledWith('/orders/99/cancel');
        // The local list should be unchanged because the index was -1
        expect(ordersStore.userOrders).toEqual(originalOrders);
    });

    it('handles error when canceling an order fails', async () => {
        const store = useOrdersStore();
        const error = {
            isAxiosError: true,
            response: {
                data: { message: 'Order not open' },
                status: 422,
                statusText: 'Unprocessable Entity',
                headers: {},
                config: {} as AxiosRequestConfig,
            },
        };
        (axios.post as Mock).mockRejectedValue(error);

        await expect(store.cancelOrder(1)).rejects.toThrow();
        expect(store.error).toBe('Order not open');
    });

    it('handles generic error when canceling an order fails', async () => {
        const store = useOrdersStore();
        (axios.post as Mock).mockRejectedValue(new Error('Network Error'));

        await expect(store.cancelOrder(1)).rejects.toThrow('Network Error');

        expect(store.error).toBe('Failed to cancel order');
    });

    it.each([
        {
            side: 'buy',
            initialBook: {
                buy: [mockOrder, { ...mockOrder, id: 2, side: 'buy' }], // Add a second buy order
                sell: [],
            },
        },
        {
            side: 'sell',
            initialBook: {
                buy: [],
                sell: [{ ...mockOrder, side: 'sell' } as Order],
            },
        },
    ])(
        'updates state on OrderMatched event for a $side order',
        ({ side, initialBook }) => {
            const ordersStore = useOrdersStore();
            const matchedOrder: Order = {
                ...mockOrder,
                side: side as 'buy' | 'sell',
            };
            ordersStore.userOrders = [
                // Ensure the user order has the correct type
                { ...matchedOrder, side: side as 'buy' | 'sell' },
            ] as Order[];
            ordersStore.orderBook = initialBook;

            // This will hold the callback passed to window.Echo.listen
            let eventCallback: (event: { order: Order }) => void;

            // Mock the Echo chain to capture the callback
            const listenMock = vi.fn((eventName, callback) => {
                eventCallback = callback;
            });
            window.Echo = {
                private: vi.fn().mockReturnThis(),
                listen: listenMock,
            };

            ordersStore.listenForMatches(1);

            expect(listenMock).toHaveBeenCalledWith(
                'OrderMatched',
                expect.any(Function),
            );

            // Simulate receiving a Pusher event
            eventCallback!({ order: { ...matchedOrder, status: 2 } }); // Status becomes "filled"

            expect(ordersStore.userOrders[0].status).toBe(2);
            if (side === 'buy') {
                expect(ordersStore.orderBook.buy).toEqual([
                    { ...mockOrder, id: 2, side: 'buy' },
                ]); // Expect only the un-matched order
            } else {
                expect(ordersStore.orderBook.sell).toHaveLength(0);
            }
        },
    );

    it('updates state on OrderMatched event for a "buy" order with multiple initial orders', () => {
        const ordersStore = useOrdersStore();
        const initialBuyOrders = [
            { ...mockOrder, id: 1, side: 'buy' },
            { ...mockOrder, id: 2, side: 'buy' }, // Order to remain
        ];
        ordersStore.orderBook.buy = initialBuyOrders;
        ordersStore.orderBook.sell = []; // Not relevant for this test
        ordersStore.userOrders = []; // Not relevant for this test

        // This will hold the callback passed to window.Echo.listen
        let eventCallback: (event: { order: Order }) => void;

        // Mock the Echo chain to capture the callback
        const listenMock = vi.fn((eventName, callback) => {
            eventCallback = callback;
        });
        window.Echo = {
            private: vi.fn().mockReturnThis(),
            listen: listenMock,
        };

        ordersStore.listenForMatches(123); // userId

        expect(listenMock).toHaveBeenCalledWith(
            'OrderMatched',
            expect.any(Function),
        );

        // Simulate receiving a Pusher event for order ID 1 (which should be removed)
        const matchedEventOrder: Order = {
            ...mockOrder,
            id: 1,
            side: 'buy',
            status: 2,
        };
        eventCallback!({ order: matchedEventOrder });

        // Expect only the order with ID 2 to remain in the buy order book
        expect(ordersStore.orderBook.buy).toEqual([
            { ...mockOrder, id: 2, side: 'buy' },
        ]);
        // Also check sell side remains untouched
        expect(ordersStore.orderBook.sell).toEqual([]);
    });
});
