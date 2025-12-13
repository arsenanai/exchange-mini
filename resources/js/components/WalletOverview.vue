<template>
    <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
        <h2 class="mb-4 text-xl font-semibold text-gray-800 dark:text-white">
            Wallet
        </h2>
        <div
            v-if="profileStore.loading"
            class="text-center text-gray-500 dark:text-gray-400"
        >
            Loading...
        </div>
        <div v-else-if="profileStore.error" class="text-center text-red-500">
            {{ profileStore.error }}
        </div>
        <div v-else class="space-y-4">
            <div>
                <h3
                    class="text-lg font-medium text-gray-700 dark:text-gray-300"
                >
                    USD Balance
                </h3>
                <p
                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                    data-testid="wallet-usd-balance"
                >
                    ${{ formattedUsdBalance }}
                </p>
            </div>
            <div>
                <h3
                    class="mb-2 text-lg font-medium text-gray-700 dark:text-gray-300"
                >
                    Asset Balances
                </h3>
                <ul v-if="balances.assets.length" class="space-y-2">
                    <li
                        v-for="asset in balances.assets"
                        :key="asset.symbol"
                        class="flex justify-between rounded-md bg-gray-50 p-3 dark:bg-gray-700"
                    >
                        <span class="font-bold text-gray-800 dark:text-white">{{
                            asset.symbol
                        }}</span>
                        <span class="text-gray-600 dark:text-gray-300"
                            >{{ asset.amount }} ({{
                                asset.lockedAmount
                            }}
                            locked)</span
                        >
                    </li>
                </ul>
                <p v-else class="text-gray-500 dark:text-gray-400">
                    No assets held.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useProfileStore } from '@/stores/profile';
import { computed, onMounted } from 'vue';

const profileStore = useProfileStore();
const balances = computed(() => profileStore.balances);
const formattedUsdBalance = computed(() => {
    const balance = parseFloat(balances.value.usd);
    if (isNaN(balance)) {
        return '0.00';
    }
    return balance.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: true,
    });
});

onMounted(() => {
    profileStore.fetchProfile();
});
</script>
