# E-Commerce Backend — Laravel + React (API)

This repository contains the backend API for an e‑commerce application built with Laravel. It serves a React frontend (separate repo) and provides models, controllers, services, authentication, file uploads, and test fixtures needed to run a production-ready API.

## Features

- RESTful JSON API for products, categories, brands, sizes, orders, users, and wishlists
- Authentication with Laravel Sanctum (API tokens)
- Product image upload and temporary image handling
- Product size / inventory relationships and validation
- Orders and order items flow with basic processing and validation
- Factories and seeders for test/dev data
- Automated tests using Pest
- Opinionated code style (Laravel Pint) and explicit types

## Tech Stack

- PHP 8.4
- Laravel v13
- MySQL / PostgreSQL / SQLite (configurable)
- Laravel Sanctum for API authentication
- Pest for testing
- Vite (integration with frontend builds)

## Quickstart — Local Development

Prerequisites:

- PHP 8.4, Composer
- Database server (MySQL/Postgres) or SQLite
- Node.js & npm (only if running frontend or building assets locally)

1. Clone the repository

	git clone <repo-url>
	cd backend

2. Install dependencies

	composer install
	cp .env.example .env
	php artisan key:generate

3. Configure `.env` with your DB credentials and storage settings.

4. Run migrations and seeders

	php artisan migrate --seed

5. Create storage symlink for public uploads

	php artisan storage:link

6. Serve the application

	php artisan serve --host=127.0.0.1 --port=8000

The API base URL will be `http://127.0.0.1:8000/api`.

## Environment Variables

Key `.env` variables to set:

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `APP_URL` — canonical application URL used for link generation
- `FILESYSTEM_DRIVER` — disk used for uploads (e.g., `public`)

Example:

APP_URL=http://127.0.0.1:8000
FILESYSTEM_DRIVER=public

## API Endpoints (high level)

See `routes/api.php` for full route list. Common endpoints:

- `POST /api/auth/register` — register a new user
- `POST /api/auth/login` — login and return Sanctum token
- `GET /api/products` — list products
- `GET /api/products/{id}` — product detail
- `POST /api/orders` — create an order (authenticated)
- `GET /api/wishlist` — wishlist CRUD (authenticated)

Use `Authorization: Bearer <token>` for protected routes.

## Testing

Run the test suite with Pest:

php artisan test --parallel

or

php artisan test --compact

Add tests to `tests/Feature` and `tests/Unit`. Use provided factories in `database/factories` to generate test data.

## Code Style & Formatting

- Format PHP code with Laravel Pint: `vendor/bin/pint --format` before committing.
- Follow PHP 8 typed signatures and constructor property promotion patterns used across the project.

## Database & Seeders

- Migrations: `database/migrations`
- Factories: `database/factories`
- Seeders: `database/seeders`

Helpful commands:

php artisan migrate
php artisan migrate:fresh --seed

## Deployment Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false` in production `.env`.
- Use a production-ready queue and cache (Redis recommended).
- Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` on deploy.
- Ensure `storage` is writable and `php artisan storage:link` was run.

## Where to Look in the Codebase

- API routes: `routes/api.php`
- Controllers: `app/Http/Controllers`
- Form Requests (validation): `app/Http/Requests`
- API Resources: `app/Http/Resources`
- Services: `app/Services`
- Models: `app/Models`

## Contributing

- Follow existing project conventions and open a pull request with a clear description.
- Run tests and Pint locally before submitting.
- Add tests when introducing behavior changes.

## Troubleshooting

- Vite manifest errors: build frontend assets (`npm run build`) or ensure the frontend dev server is running if developing locally.
- Database connection errors: verify `.env` DB settings and DB server availability.

## License

Add the appropriate license (e.g., MIT) to this repository. Replace this section with your license details.

---

Want improvements? I can:

- add example curl requests and Postman collection
- generate OpenAPI/Swagger documentation
- add CI workflow for tests & static checks


