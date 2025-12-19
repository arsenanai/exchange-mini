import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Read environment variables from .env.testing file
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, '.env.testing') });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
    testDir: './resources/js/tests/e2e',
    /* Run tests in files in parallel */
    fullyParallel: true,
    /* Fail the build on CI if you accidentally left test.only in the source code. */
    forbidOnly: !!process.env.CI,
    /* Retry on CI only */
    retries: process.env.CI ? 2 : 0,
    /* Opt out of parallel tests on CI. */
    workers: process.env.CI ? 1 : undefined,
    /* Reporter to use. See https://playwright.dev/docs/test-reporters */
    reporter: 'list',

    /* Global setup to run before all tests */
    globalSetup: './resources/js/tests/e2e/global.setup.ts',

    /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
    use: {
        /* Base URL to use in actions like `await page.goto('/')`. The frontend dev server for testing runs on 5174. */
        baseURL: 'http://localhost:8000',

        /* Whether to run tests in headed mode. Controlled by .env.testing */
        headless: process.env.CI ? true : process.env.PLAYWRIGHT_HEADED === 'false',

        /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
        trace: 'on-first-retry',
    },

    /* Configure projects for major browsers */
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    /* Conditionally run local dev server before starting the tests, only in CI */
    webServer: process.env.CI
        ? [
              {
                  command: 'php artisan serve --env=testing --port=8000',
                  url: 'http://127.0.0.1:8000/api/profile', // Wait for the backend API to be ready
                  reuseExistingServer: false,
                  timeout: 120 * 1000, // 2 minutes
              },
              {
                  command: 'npm run dev:testing',
                  url: 'http://127.0.0.1:5174', // Wait for the frontend to be ready
                  reuseExistingServer: false,
                  timeout: 120 * 1000, // 2 minutes
              },
          ]
        : undefined,
});