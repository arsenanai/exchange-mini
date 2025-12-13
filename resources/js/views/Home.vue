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
                Welcome to Exchange Mini
            </h1>
            <div class="flex flex-col space-y-4">
                <router-link
                    to="/login"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-center font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                    Login
                </router-link>
                <router-link
                    to="/register"
                    class="w-full rounded-md bg-gray-200 px-4 py-2 text-center font-medium text-indigo-700 hover:bg-gray-300 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                    Register
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
// No script needed
</script>
