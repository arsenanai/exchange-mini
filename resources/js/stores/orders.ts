import axios from 'axios';
import { defineStore } from 'pinia';
import { useProfileStore } from './profile';
import type { NewOrder, Order } from './types';

interface OrdersState {
    userOrders: Order[];
    orderBook: {
        buy: Order[];
        sell: Order[];
    };
    loading: boolean;
    error: string | null;
}

export const useOrdersStore = defineStore('orders', {
    state: (): OrdersState => ({
        userOrders: [],
        orderBook: { buy: [], sell: [] },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchUserOrders() {
            this.loading = true;
            try {
                const response = await axios.get('/orders/all');
                this.userOrders = response.data.data;
            } catch (err: any) {
                this.error =
                    err.response?.data?.message ||
                    'Failed to fetch user orders';
            } finally {
                this.loading = false;
            }
        },

        async fetchOrderBook(symbol: string) {
            this.loading = true;
            try {
                const response = await axios.get(`/orders?symbol=${symbol}`);
                const orders: Order[] = response.data.data;
                this.orderBook.buy = orders
                    .filter((o) => o.side === 'buy')
                    .sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                this.orderBook.sell = orders
                    .filter((o) => o.side === 'sell')
                    .sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            } catch (err: any) {
                this.error =
                    err.response?.data?.message || 'Failed to fetch order book';
            } finally {
                this.loading = false;
            }
        },

        async createOrder(order: NewOrder) {
            this.error = null;
            try {
                const response = await axios.post('/orders', order);
                const newOrder: Order = response.data.order;
                this.userOrders.unshift(newOrder);

                // Refresh profile to get updated balances
                const profileStore = useProfileStore();
                await profileStore.fetchProfile();

                return newOrder;
            } catch (err: any) {
                this.error =
                    err.response?.data?.message || 'Failed to create order';
                throw err;
            }
        },

        async cancelOrder(orderId: number) {
            this.error = null;
            try {
                const response = await axios.post(`/orders/${orderId}/cancel`);
                const cancelledOrder: Order = response.data.data;

                const index = this.userOrders.findIndex(
                    (o) => o.id === orderId,
                );
                if (index !== -1) {
                    this.userOrders[index] = cancelledOrder;
                }

                // Refresh profile to get updated balances
                const profileStore = useProfileStore();
                await profileStore.fetchProfile();

                return cancelledOrder;
            } catch (err: any) {
                this.error =
                    err.response?.data?.message || 'Failed to cancel order';
                throw err;
            }
        },

        listenForMatches(userId: number) {
            (window as any).Echo.private(`user.${userId}`).listen(
                'OrderMatched',
                (event: any) => {
                    // Update the status of the matched order in the user's order list
                    const orderIndex = this.userOrders.findIndex(
                        (o) => o.id === event.order.id,
                    );
                    if (orderIndex !== -1) {
                        this.userOrders[orderIndex].status = event.order.status;
                    }

                    // Remove the matched order from the public order book
                    if (event.order.side === 'buy') {
                        this.orderBook.buy = this.orderBook.buy.filter(
                            (o) => o.id !== event.order.id,
                        );
                    } else {
                        this.orderBook.sell = this.orderBook.sell.filter(
                            (o) => o.id !== event.order.id,
                        );
                    }

                    // Refresh profile and orders to ensure data consistency
                    const profileStore = useProfileStore();
                    profileStore.fetchProfile();
                    this.fetchUserOrders();
                },
            );
        },
    },
});
