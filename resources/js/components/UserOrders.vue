<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <h2 class="mb-4 text-xl font-semibold text-gray-800 dark:text-white">
            My Orders
        </h2>
        <div
            v-if="ordersStore.loading"
            class="text-center text-gray-500 dark:text-gray-400"
        >
            Loading orders...
        </div>
        <div
            v-else-if="!ordersStore.userOrders.length"
            class="text-center text-gray-500 dark:text-gray-400"
        >
            You have no orders.
        </div>
        <div v-else class="overflow-x-auto">
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Symbol
                        </th>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Side
                        </th>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Price
                        </th>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Amount
                        </th>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Status
                        </th>
                        <th
                            class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                        >
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
                >
                    <tr v-for="order in ordersStore.userOrders" :key="order.id">
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap text-gray-900 dark:text-white"
                        >
                            {{ order.symbol }}
                        </td>
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap"
                            :class="
                                order.side === 'buy'
                                    ? 'text-green-500'
                                    : 'text-red-500'
                            "
                        >
                            {{ order.side }}
                        </td>
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                        >
                            {{ order.price }}
                        </td>
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                        >
                            {{ order.amount }}
                        </td>
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                        >
                            {{ statusText(order.status) }}
                        </td>
                        <td
                            class="px-4 py-2 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300"
                        >
                            <button
                                v-if="order.status === 1"
                                class="text-red-600 hover:text-red-900"
                                @click="ordersStore.cancelOrder(order.id)"
                            >
                                Cancel
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useOrdersStore } from '@/stores/orders';
import { onMounted } from 'vue';

const ordersStore = useOrdersStore();

const statusText = (status: 1 | 2 | 3) => {
    const map = {
        1: 'Open',
        2: 'Filled',
        3: 'Cancelled',
    };
    return map[status];
};

onMounted(() => {
    ordersStore.fetchUserOrders();
});
</script>
