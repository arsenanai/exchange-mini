import { vi } from 'vitest'; // eslint-disable-line

// Mock the router to prevent navigation errors in tests
vi.mock('@/router', () => ({
    default: {
        push: vi.fn(),
    },
}));

// Mock the entire laravel-echo module
vi.mock('laravel-echo', () => {
    return {
        // The default export is the Echo class. We mock its constructor.
        default: vi.fn().mockImplementation(() => ({
            private: vi.fn().mockReturnThis(),
            listen: vi.fn(),
        })),
    };
});

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
