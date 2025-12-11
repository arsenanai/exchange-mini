<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <h2 class="mb-4 text-xl font-semibold text-gray-800 dark:text-white">
            Place Order
        </h2>
        <form class="space-y-4" @submit.prevent="handlePlaceOrder">
            <div>
                <label for="symbol" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Symbol</label>
                <select
id="symbol" v-model="form.symbol" required
                    class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
            </div>

            <div>
                <label for="side" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Side</label>
                <select
id="side" v-model="form.side" required
                    class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="buy">Buy</option>
                    <option value="sell">Sell</option>
                </select>
            </div>

            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price
                    (USD)</label>
                <input
id="price" v-model="form.price" type="number" step="0.01" required
                    class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                <input
id="amount" v-model="form.amount" type="number" step="0.0001" required
                    class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>

            <div v-if="ordersStore.error" class="text-sm text-red-500">
                {{ ordersStore.error }}
            </div>

            <div v-if="successMessage" class="text-sm text-green-500">
                {{ successMessage }}
            </div>

            <button
type="submit" :disabled="isSubmitting"
                class="w-full rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50">
                {{ isSubmitting ? 'Placing Order...' : 'Place Order' }}
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
import { useOrdersStore } from '@/stores/orders';
import type { NewOrder } from '@/stores/types';
import { ref } from 'vue';

const ordersStore = useOrdersStore();
const form = ref<NewOrder>({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: '',
});
const successMessage = ref<string>('');

const handlePlaceOrder = async () => {
    successMessage.value = '';
    try {
        await ordersStore.createOrder(form.value);
        successMessage.value = 'Order placed successfully!';
        // Reset form
        form.value.price = '';
        form.value.amount = '';
        setTimeout(() => (successMessage.value = ''), 3000);
    } catch (error) {
        // Error is handled in the store and displayed via ordersStore.error
        console.error('Failed to place order:', error);
    }
};
</script>
