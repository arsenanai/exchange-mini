<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

use function Pest\Laravel\browse;

uses(DatabaseMigrations::class);

test('user can register, login, and manage orders', function () {
    browse(function (Browser $browser) {
        // 1. Registration
        $browser->visit('/register')
            ->assertSee('Create an account')
            ->type('name', 'Dusk User')
            ->type('email', 'dusk@example.com')
            ->type('password', 'password123')
            ->press('Register')
            ->waitForLocation('/')
            ->assertPathIs('/')
            ->assertSee('Exchange Mini');

        // 2. Initial State & Logout
        // The default user from the seeder has 10,000 USD.
        $browser->waitForText('Wallet')
            ->assertSeeIn('div.space-y-4 > div:nth-child(1) > p:nth-child(2)', '$10000.00');

        $browser->press('Logout')
            ->waitForLocation('/login')
            ->assertPathIs('/login');

        // 3. Login
        $browser->visit('/login')
            ->assertSee('Login to your account')
            ->type('email', 'dusk@example.com')
            ->type('password', 'password123')
            ->press('Login')
            ->waitForLocation('/')
            ->assertPathIs('/');

        // 4. Place a Buy Order
        $browser->waitForText('Place Order')
            ->select('symbol', 'BTC')
            ->select('side', 'buy')
            ->type('price', '20000')
            ->type('amount', '0.1') // Cost: 2000 USD
            ->press('Place Order')
            ->waitForText('Order placed successfully!');

        // 5. Verify Order and Balance Update
        $browser->waitFor('.divide-y') // Wait for orders table
            ->assertSeeIn('tbody', 'BTC')
            ->assertSeeIn('tbody', 'buy')
            ->assertSeeIn('tbody', 'Open');

        // Balance should be 10000 - 2000 = 8000
        $browser->assertSeeIn('div.space-y-4 > div:nth-child(1) > p:nth-child(2)', '$8000.00');

        // 6. Cancel the Order
        $browser->press('Cancel')
            ->waitForText('Cancelled');

        // 7. Verify Balance is Restored
        // The fetchProfile call after cancellation should restore the balance.
        $browser->waitForText('$10000.00', 5); // Wait up to 5 seconds for the balance to update
        $browser->assertSeeIn('div.space-y-4 > div:nth-child(1) > p:nth-child(2)', '$10000.00');
    });
});
