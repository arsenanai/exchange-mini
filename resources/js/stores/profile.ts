import axios from 'axios';
import { defineStore } from 'pinia';
import type { User } from './types';

interface ProfileState {
    user: User | null;
    loading: boolean;
    error: string | null;
}

export const useProfileStore = defineStore('profile', {
    state: (): ProfileState => ({
        user: null,
        loading: false,
        error: null,
    }),

    getters: {
        balances: (state) => {
            if (!state.user) return { usd: '0.00', assets: [] };
            return {
                usd: state.user.balanceUsd,
                assets: state.user.assets,
            };
        },
    },

    actions: {
        async fetchProfile() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get('/profile');
                this.user = response.data.data;
            } catch (err: any) {
                this.error =
                    err.response?.data?.message || 'Failed to fetch profile';
            } finally {
                this.loading = false;
            }
        },
    },
});
