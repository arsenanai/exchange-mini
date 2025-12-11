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

export interface LoginCredentials {
    email: string;
    password?: string;
}

export type RegisterInfo = LoginCredentials & { name: string };
