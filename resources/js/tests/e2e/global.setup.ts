import { request } from '@playwright/test';

async function globalSetup() {
    console.log('Running global setup...');

    // 1. Clear config and run migrations against the test database.
    console.log('Migrating database...');
    const requestContext = await request.newContext();

    // Clear any cached config to ensure .env.testing is loaded.
    await requestContext.post(
        'http://127.0.0.1:8000/_testing/artisan/config:clear',
    );

    // Run fresh migrations and seeders.
    const response = await requestContext.post(
        'http://127.0.0.1:8000/_testing/artisan/migrate:fresh',
    );

    if (!response.ok()) {
        console.error('Failed to migrate database:', await response.text());
        throw new Error(
            `Failed to migrate database: ${response.status()} ${response.statusText()}`,
        );
    }
    console.log('Database migrated successfully.');
    await requestContext.dispose();
}

export default globalSetup;
