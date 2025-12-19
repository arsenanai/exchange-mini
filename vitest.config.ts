import { loadEnv } from 'vite';
import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.ts';

export default defineConfig((config) => {
    // Load .env.testing variables into process.env
    const env = loadEnv('testing', process.cwd(), '');

    return mergeConfig(viteConfig, {
        test: {
            globals: true,
            environment: 'jsdom',
            setupFiles: 'resources/js/tests/setup.ts',
            // Explicitly include only unit test files
            include: [
                'resources/js/tests/**/*.test.ts',
                'resources/js/components/**/*.test.ts',
            ],
            env, // Make env variables available to tests
            coverage: {
                provider: 'v8',
                reporter: ['text', 'json-summary', 'html'],
                // Adjust coverage paths to be relative to the project root
                include: ['resources/js/components/**', 'resources/js/stores/**'],
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