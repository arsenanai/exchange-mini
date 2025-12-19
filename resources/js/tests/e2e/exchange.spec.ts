import { expect, test } from '@playwright/test';

test('user can register, login, and manage orders', async ({ page }) => {
    // Conditionally enable verbose logging based on environment variable
    if (process.env.PLAYWRIGHT_DEBUG_LOGGING === 'true') {
        // Forward browser console logs to the terminal
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                console.error(`Browser Console ERROR: ${msg.text()}`);
            } else {
                // Optional: log other message types if needed
                console.log(`Browser Console: [${msg.type()}] ${msg.text()}`);
            }
        });

        // Forward all HTTP requests and responses to the terminal for debugging
        const resourceTypesToLog = ['xhr', 'fetch', 'websocket'];
        page.on('request', (request) => {
            if (resourceTypesToLog.includes(request.resourceType())) {
                console.log(`>> ${request.method()} ${request.url()}`);
            }
        });
        page.on('response', async (response) => {
            if (
                resourceTypesToLog.includes(response.request().resourceType())
            ) {
                const status = response.status();
                const text = `<< ${status} ${response.request().method()} ${response.url()}`;
                if (status >= 400) {
                    console.error(text);
                    try {
                        const body = await response.text();
                        console.error(`   Body: ${body}`);
                    } catch {
                        // Ignore errors reading the body, e.g. for non-text responses.
                    }
                } else {
                    // Log successful responses to see the full flow
                    console.log(text);
                }
            }
        });
    }

    // 1. Registration
    await page.goto('/');
    await expect(page.getByText('Welcome to Exchange Mini')).toBeVisible();
    await page.getByRole('link', { name: 'Register' }).click();

    await expect(
        page.getByRole('heading', { name: 'Create an account' }),
    ).toBeVisible();
    await page.getByLabel('Name').fill('Playwright User');
    await page.getByLabel('Email address').fill('playwright@example.com');
    await page.getByLabel('Password').fill('password123');

    // Wait for the register API response
    const registerResponsePromise = page.waitForResponse('**/api/register');
    await page.getByRole('button', { name: 'Register' }).click();
    const registerResponse = await registerResponsePromise;

    if (process.env.PLAYWRIGHT_DEBUG_LOGGING === 'true') {
        const status = registerResponse.status();
        let body = '';
        try {
            body = await registerResponse.text();
        } catch {
            body = '[Could not read response body]';
        }
        console.log(`Debug: /api/register Response - Status: ${status}, Body: ${body}`);
    }

    // Wait for the login API response after successful registration and implicit login
    const loginResponsePromise = page.waitForResponse('**/api/login');
    const loginResponse = await loginResponsePromise;

    if (process.env.PLAYWRIGHT_DEBUG_LOGGING === 'true') {
        const status = loginResponse.status();
        let body = '';
        try {
            body = await loginResponse.text();
        } catch {
            body = '[Could not read response body]';
        }
        console.log(`Debug: /api/login Response - Status: ${status}, Body: ${body}`);
        console.log('Debug: Token in localStorage after login:', await page.evaluate(() => localStorage.getItem('token')));
    }

    // After registration/login, we should be on the exchange page
    await expect(page).toHaveURL('/exchange');

    // 2. Verify Initial State & Logout
    // The default user created by migrate:fresh (via a seeder) has 10,000 USD.
    // A newly registered user starts with a balance of 10,000 USD.
    await expect(page.getByTestId('wallet-usd-balance')).toContainText(
        '$10,000.00',
    );

    await page.getByRole('button', { name: 'Logout' }).click();
    await expect(page).toHaveURL('/login');

    // 3. Login
    await page.goto('/');
    await page.getByRole('link', { name: 'Login' }).click();
    await expect(
        page.getByRole('heading', { name: 'Login to your account' }),
    ).toBeVisible();
    await page.getByLabel('Email address').fill('playwright@example.com');
    await page.getByLabel('Password').fill('password123');
    await page.getByRole('button', { name: 'Login' }).click();

    await page.waitForURL('/exchange');

    // 4. Place a Buy Order
    await page.locator('#symbol').selectOption('BTC');
    await page.locator('#side').selectOption('buy');
    await page.locator('#price').fill('20000');
    await page.locator('#amount').fill('0.1'); // Cost: 2000 USD

    // Wait for the API call to complete *before* asserting UI changes.
    // This is more reliable than waiting for a UI element that appears as a result of the call.
    await Promise.all([
        page.waitForResponse('**/api/orders'),
        page.getByRole('button', { name: 'Place Order' }).click(),
    ]);

    // Wait for the profile to be refetched after order creation, which updates balances.
    // This is crucial because createOrder in the store awaits profileStore.fetchProfile().
    await page.waitForResponse('**/api/profile');

    await expect(page.getByText('Order placed successfully!')).toBeVisible({ timeout: 10000 });

    // 5. Verify Order and Balance Update
    // We use a test ID to reliably find the row, as its content is dynamic.
    const orderRow = page.locator('[data-testid^="order-row-"]');
    await expect(orderRow).toContainText('BTC');
    await expect(orderRow).toContainText('buy');
    await expect(orderRow).toContainText('Open');

    // Balance should be 10,000 - 2,000 = 8,000
    // We use `waitFor` to give the frontend time to update after the API call.
    await expect(page.getByTestId('wallet-usd-balance')).toContainText(
        '$8,000.00',
    );

    // 6. Cancel the Order
    // We explicitly wait for the 'cancel' API call to finish before asserting UI changes.
    // This prevents race conditions where the test checks the UI too quickly.
    await Promise.all([
        page.waitForResponse(
            (resp) => resp.url().includes('/cancel') && resp.status() === 200,
        ),
        orderRow.getByRole('button', { name: 'Cancel' }).click(),
    ]);
    await expect(orderRow.getByTestId('order-status')).toHaveText('Cancelled');

    // 7. Verify Balance is Restored
    // After cancellation, the locked funds are returned.
    await expect(page.getByTestId('wallet-usd-balance')).toContainText(
        '$10,000.00',
    );
});
