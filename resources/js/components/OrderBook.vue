<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Order Book
            </h2>
            <select
                v-model="selectedSymbol"
                class="rounded-md border border-gray-300 bg-gray-50 px-3 py-1 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div
            v-if="ordersStore.loading"
            class="text-center text-gray-500 dark:text-gray-400"
        >
            Loading order book...
        </div>
        <div v-else class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <!-- Sell Orders (Asks) -->
            <div>
                <h3 class="mb-2 text-lg font-semibold text-red-500">Sells</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th
                                class="py-1 text-left text-gray-500 dark:text-gray-400"
                            >
                                Price (USD)
                            </th>
                            <th
                                class="py-1 text-right text-gray-500 dark:text-gray-400"
                            >
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in ordersStore.orderBook.sell"
                            :key="order.id"
                        >
                            <td class="py-1 text-red-600 dark:text-red-400">
                                {{
                                    parseFloat(order.price).toLocaleString(
                                        'en-US',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        },
                                    )
                                }}
                            </td>
                            <td
                                class="py-1 text-right text-gray-700 dark:text-gray-300"
                            >
                                {{ order.amount }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Buy Orders (Bids) -->
            <div>
                <h3 class="mb-2 text-lg font-semibold text-green-500">Buys</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th
                                class="py-1 text-left text-gray-500 dark:text-gray-400"
                            >
                                Price (USD)
                            </th>
                            <th
                                class="py-1 text-right text-gray-500 dark:text-gray-400"
                            >
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in ordersStore.orderBook.buy"
                            :key="order.id"
                        >
                            <td class="py-1 text-green-600 dark:text-green-400">
                                {{
                                    parseFloat(order.price).toLocaleString(
                                        'en-US',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        },
                                    )
                                }}
                            </td>
                            <td
                                class="py-1 text-right text-gray-700 dark:text-gray-300"
                            >
                                {{ order.amount }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useOrdersStore } from '@/stores/orders';
import { onMounted, ref, watch } from 'vue';

const ordersStore = useOrdersStore();
const selectedSymbol = ref<'BTC' | 'ETH'>('BTC');

const fetchBook = () => {
    ordersStore.fetchOrderBook(selectedSymbol.value);
};

onMounted(() => {
    fetchBook();
});

watch(selectedSymbol, () => {
    fetchBook();
});
</script>
