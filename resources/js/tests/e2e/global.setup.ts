import { request } from '@playwright/test';

async function globalSetup() {
    console.log('Running global setup...');

    // 1. Clear config and run migrations against the test database.
    console.log('Migrating database...');
    const requestContext = await request.newContext();

    // Clear any cached config to ensure .env.testing is loaded.
    const configClearResponse = await requestContext.post(
        'http://127.0.0.1:8000/_testing/artisan/config:clear',
    );
    if (!configClearResponse.ok()) {
        console.error(
            'Failed to clear config:',
            await configClearResponse.text(),
        );
        throw new Error('Failed to clear config during setup.');
    }
    console.log('Configuration cleared successfully.');

    // Run fresh migrations and seeders.
    const migrateResponse = await requestContext.post(
        'http://127.0.0.1:8000/_testing/artisan/migrate:fresh',
    );

    if (!migrateResponse.ok()) {
        console.error('Failed to migrate database:', await migrateResponse.text());
        throw new Error(
            `Failed to migrate database: ${migrateResponse.status()} ${migrateResponse.statusText()}`,
        );
    }
    console.log('Database migrated successfully.');
    await requestContext.dispose();
}

export default globalSetup;
