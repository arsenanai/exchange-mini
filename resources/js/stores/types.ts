export interface LoginCredentials {
    email: string;
    password?: string;
}

export interface RegisterInfo extends LoginCredentials {
    name: string;
    password_confirmation?: string;
}

export interface Asset {
    id: number;
    symbol: string;
    amount: string;
    lockedAmount: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    balanceUsd: string;
    assets: Asset[];
}

export interface Order {
    id: number;
    userId: number;
    symbol: 'BTC' | 'ETH';
    side: 'buy' | 'sell';
    price: string;
    amount: string;
    status: 1 | 2 | 3; // 1: open, 2: filled, 3: cancelled
    lockedUsd: string;
    lockedAsset: string;
    createdAt: string;
    updatedAt: string;
}

export interface NewOrder {
    symbol: 'BTC' | 'ETH';
    side: 'buy' | 'sell';
    price: string;
    amount: string;
}

export interface AuthState {
    user: User | null;
    token: string | null;
}
