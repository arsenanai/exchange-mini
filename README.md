# Exchange Mini

Limit-order mini exchange engine with Laravel + Vue + Pusher broadcasting. Focuses on:
- Atomic matching with DB transactions and `SELECT ... FOR UPDATE` row locks
- Race-safe balance/asset locking
- Consistent commission: 1.5% of USD value; buyer pays USD fee, seller pays asset fee
- Real-time updates to both counterparties via private channels

## Stack
- Backend: Laravel (Sanctum for auth, Pusher for broadcasting)
- DB: MySQL
- Frontend: Vue 3 (Composition API), Tailwind, Laravel Echo + Pusher

## Setup

1. `composer install`
2. Configure `.env` with your database credentials and Pusher keys. You will also need to add `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` for the frontend.
3. `php artisan key:generate`
4. `php artisan migrate`
5. `npm install`
6. `npm run build`
7. Run the development server: `php artisan serve`
8. In a separate terminal, run the Vite dev server: `npm run dev`
9. In another terminal, run the queue worker: `php artisan queue:work`

### Auth
- Register/login via `/api/register`, `/api/login`. The frontend has a simple login page.
- Sanctum bearer token is stored in localStorage.

### API Endpoints
- `GET /api/profile` — USD + asset balances
- `GET /api/orders?symbol=BTC` — Orderbook (open orders)
- `GET /api/orders/all` — User orders (open + filled + cancelled)
- `POST /api/orders` — Create limit order (locks funds/assets, attempts match)
- `POST /api/orders/{id}/cancel` — Cancel open order (releases locks)

### Matching Rules
- Full match only (no partials)
- BUY matches first SELL where `sell.price <= buy.price`
- SELL matches first BUY where `buy.price >= sell.price`
- Commission = 1.5% USD volume
  - Buyer pays USD fee at match time (deducted from balance)
  - Seller pays asset fee (deducted from delivered asset to buyer)
- Real-time broadcast `OrderMatched` on private channels `user.{id}` for both sides.

### Notes
- An initial match is attempted synchronously in `OrderController::create`. A job is also dispatched to the `matching` queue to handle further matching possibilities, ensuring responsiveness.
- Prices/amounts use decimal(18,8) and bc math for precision.
- Cancelling releases locked USD or asset atomically.
- Frontend listens to `OrderMatched` and refreshes profile, orders, and orderbook.

## Security
- Private channels require auth via Sanctum bearer token.
- Basic validation on inputs and ownership checks on cancel.
