<template>
    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900">
        <header
            class="flex items-center justify-between bg-white p-4 shadow-md dark:bg-gray-800"
        >
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                Exchange Mini
            </h1>
            <button
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                @click="handleLogout"
            >
                Logout
            </button>
        </header>

        <main class="flex-grow space-y-8 p-4 md:p-8">
            <WalletOverview />

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <OrderForm />
                </div>
                <div class="lg:col-span-2">
                    <UserOrders />
                </div>
            </div>

            <OrderBook />
        </main>
    </div>
</template>

<script setup lang="ts">
import OrderBook from '@/components/OrderBook.vue';
import OrderForm from '@/components/OrderForm.vue';
import UserOrders from '@/components/UserOrders.vue';
import WalletOverview from '@/components/WalletOverview.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const handleLogout = async () => {
    await authStore.logout();
};
</script>
