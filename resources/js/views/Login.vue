<template>
    <div
        class="flex min-h-screen items-center justify-center bg-gray-100 dark:bg-gray-900"
    >
        <div
            class="w-full max-w-md space-y-6 rounded-lg bg-white p-8 shadow-md dark:bg-gray-800"
        >
            <h1
                class="text-center text-2xl font-bold text-gray-900 dark:text-white"
            >
                Login to your account
            </h1>
            <form class="space-y-6" @submit.prevent="handleLogin">
                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Email address</label
                    >
                    <input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        required
                        class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    />
                </div>
                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Password</label
                    >
                    <input
                        id="password"
                        v-model="form.password"
                        name="password"
                        type="password"
                        required
                        class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    />
                </div>
                <div v-if="errorMessage" class="text-sm text-red-500">
                    {{ errorMessage }}
                </div>
                <div>
                    <button
                        type="submit"
                        class="w-full rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Login
                    </button>
                </div>
            </form>
            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                Don't have an account?
                <router-link
                    to="/register"
                    class="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    Register here
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import type { LoginCredentials } from '@/stores/types';
import { ref } from 'vue';

const authStore = useAuthStore();
const form = ref<LoginCredentials>({ email: '', password: '' });
const errorMessage = ref<string>('');

const handleLogin = async (): Promise<void> => {
    errorMessage.value = '';
    try {
        await authStore.login(form.value);
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as {
                response?: { data?: { message?: string } };
            };
            errorMessage.value =
                axiosError.response?.data?.message ||
                'An error occurred during login.';
        } else {
            errorMessage.value = 'An unexpected error occurred during login.';
        }
        window.console.error(error);
    }
};
</script>
