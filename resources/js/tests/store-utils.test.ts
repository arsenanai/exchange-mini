/// <reference types="vitest/globals" />

import { describe, expect, it } from 'vitest';
import { processOrderBook } from '../stores/orders';
import type { Order } from '../stores/types';

const mockOrders: Order[] = [
    { id: 1, side: 'sell', price: '51000', amount: '0.5' } as Order,
    { id: 2, side: 'buy', price: '49000', amount: '0.2' } as Order,
    { id: 3, side: 'sell', price: '50500', amount: '0.1' } as Order,
    { id: 4, side: 'buy', price: '49500', amount: '0.3' } as Order,
    { id: 5, side: 'buy', price: '49000', amount: '0.4' } as Order, // Same price as #2
];

describe('Store Utils - processOrderBook', () => {
    it('returns empty arrays for empty input', () => {
        const result = processOrderBook([]);
        expect(result.buy).toEqual([]);
        expect(result.sell).toEqual([]);
    });

    it('correctly filters and sorts buy and sell orders', () => {
        const result = processOrderBook(mockOrders);

        // Sells should be sorted ascending by price
        expect(result.sell).toHaveLength(2);
        expect(result.sell.map((o) => o.id)).toEqual([3, 1]);
        expect(result.sell[0].price).toBe('50500');
        expect(result.sell[1].price).toBe('51000');

        // Buys should be sorted descending by price
        expect(result.buy).toHaveLength(3);
        expect(result.buy.map((o) => o.id)).toEqual([4, 2, 5]);
        expect(result.buy[0].price).toBe('49500');
        expect(result.buy[1].price).toBe('49000');
        expect(result.buy[2].price).toBe('49000');
    });

    it('handles only buy orders', () => {
        const buyOrders = mockOrders.filter((o) => o.side === 'buy');
        const result = processOrderBook(buyOrders);
        expect(result.sell).toEqual([]);
        expect(result.buy).toHaveLength(3);
        expect(result.buy.map((o) => o.id)).toEqual([4, 2, 5]);
    });

    it('handles only sell orders', () => {
        const sellOrders = mockOrders.filter((o) => o.side === 'sell');
        const result = processOrderBook(sellOrders);
        expect(result.buy).toEqual([]);
        expect(result.sell).toHaveLength(2);
        expect(result.sell.map((o) => o.id)).toEqual([3, 1]);
    });
});
