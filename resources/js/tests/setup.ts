import { vi } from 'vitest'; // eslint-disable-line

vi.mock('@/router', () => ({
    default: {
        push: vi.fn(),
    },
}));

// Mock localStorage for jsdom environment
const localStorageMock = (function () {
    let store: Record<string, string> = {};
    return {
        getItem: function (key: string) {
            return store[key] || null;
        },
        setItem: function (key: string, value: string) {
            store[key] = value.toString();
        },
        removeItem: function (key: string) {
            delete store[key];
        },
        clear: function (): void {
            store = {};
        },
    };
})();

Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
});

// Mock Laravel Echo for tests
Object.defineProperty(window, 'Echo', {
    value: {
        private: vi.fn().mockReturnThis(),
        listen: vi.fn(),
    },
    writable: true,
});
