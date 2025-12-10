# Exchange Mini

A full-stack, limit-order mini exchange engine built with Laravel and Vue.js. This project demonstrates best practices for building real-time, financially-oriented applications with a focus on data integrity, concurrency safety, and code quality.

## Core Features

- **User Authentication**: Secure registration, login, and session management using Laravel Sanctum.
- **Atomic Order & Balance Management**: Race-condition-safe creation and cancellation of orders using database transactions and `SELECT ... FOR UPDATE` row locks.
- **Asynchronous Order Matching**: A queue-based job system for matching buy and sell orders without blocking API responses.
- **Real-Time Updates**: Instant UI updates on successful matches via private Pusher channels, powered by Laravel Echo.
- **Consistent API**: A `camelCase` JSON API enforced by Laravel's API Resources.
- **Robust Validation**: Thin controllers and strong validation using Form Request classes.
- **API Documentation**: A complete OpenAPI (Swagger) specification generated directly from code annotations.

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2
- **Frontend**: Vue 3 (Composition API), Vite, Tailwind CSS
- **Database**: MySQL / PostgreSQL
- **Real-time**: Pusher via Laravel Broadcasting & Laravel Echo
- **Testing**: Pest (Backend), Vitest (Frontend)
- **Code Quality**: Laravel Pint (Formatting), Larastan (Static Analysis)

## Setup

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & npm
- A database server (e.g., MySQL, PostgreSQL)

### 1. Installation

A convenient setup script is included to handle most of the installation steps.

```bash
# Clone the repository
git clone https://github.com/your-username/exchange-mini.git
cd exchange-mini

# Run the automated setup script
composer setup
```

This script will:
- Install Composer dependencies.
- Create a `.env` file from `.env.example`.
- Generate an application key.
- Run database migrations.
- Install NPM dependencies.
- Build frontend assets.

### 2. Environment Configuration

You must manually edit the `.env` file to add your database and Pusher credentials.

```ini
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exchange_mini
DB_USERNAME=root
DB_PASSWORD=

PUSHER_APP_ID="..."
PUSHER_APP_KEY="..."
PUSHER_APP_SECRET="..."
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER="mt1"

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 3. Running the Application

A single command starts the PHP server, Vite dev server, and queue worker concurrently.

```bash
# Start all development services
composer run dev
```

Your application will be available at `http://localhost:8000`.

## Development & Quality Tools

The project is equipped with tools to maintain high code quality.

### Code Quality & Testing

```bash
# Format code with Laravel Pint (PSR-12)
composer format

# Run static analysis with Larastan
composer analyse

# Run the backend test suite with Pest
composer test
```

### API Documentation & Verification

The project uses OpenAPI annotations to generate a browsable documentation UI. After running the server, you can view the interactive API documentation at `http://localhost:8000/api/documentation`.

```bash
# Generate or update the OpenAPI specification
php artisan l5-swagger:generate

# Verify live API endpoints against the OpenAPI spec
./tests/api-test.sh
```
