import { loadEnv } from 'vite';
import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.js';

export default defineConfig((config) => {
    // Load .env.testing variables into process.env
    const env = loadEnv('testing', process.cwd(), '');

    return mergeConfig(viteConfig, {
        test: {
            globals: true,
            environment: 'jsdom',
            root: 'resources/js',
            setupFiles: 'tests/setup.ts',
            env, // Make env variables available to tests
            exclude: ['tests/e2e/**'],
            coverage: {
                provider: 'v8',
                reporter: ['text', 'json-summary', 'html'],
                include: ['components/**', 'stores/**'],
                exclude: [
                    'tests/**',
                    'types.ts',
                    'stores/types.ts',
                    'router.ts',
                    'bootstrap.ts',
                    'app.ts',
                ],
            },
        },
    });
});