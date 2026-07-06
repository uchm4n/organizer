# Organizer

**A single private workspace API for one person — notes, todos, spreadsheets, tax declarations, calendar/events, and a personal document vault (passport, ID, driver's license, contracts) — all in one JSON-first Laravel API.**

Organizer is an **API-only** project. There is no frontend in this repository; mobile and web clients consume it over HTTP. It is highly customizable around the needs of an individual user rather than a team or enterprise.

---

## Table of contents

- [What it is](#what-it-is)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Running the app](#running-the-app)
- [API overview](#api-overview)
- [Architecture](#architecture)
- [Conventions & gotchas](#conventions--gotchas)
- [Development workflow](#development-workflow)
- [Testing](#testing)
- [Project structure](#project-structure)
- [Extending the API](#extending-the-api)
- [Deployment](#deployment)
- [Contributing & code style](#contributing--code-style)
- [License](#license)

---

## What it is

Organizer is a personal information hub served as a JSON API. The domains it covers:

- **Notes** — freeform text notes
- **Todos** — task tracking
- **Remote spreadsheet** — tabular data editable from anywhere
- **Tax declarations** — structured tax-filing data
- **Calendar & events** — scheduling and event tracking
- **Document vault** — secure storage for personal documents (passport, ID, driver's license, contracts)

Everything belongs to a single authenticated user. The API is token-authenticated (Laravel Sanctum), versioned via a request header, and returns errors as RFC 9457 Problem+JSON documents. It is designed to be the single backend a person points their phone and laptop at.

This repository contains **only the API**. Mobile and web clients are separate projects.

---

## Tech stack

| Layer | Choice |
|---|---|
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum 4 (bearer tokens) |
| API payloads | spatie/laravel-data 4 (typed DTOs at the API boundary) |
| Database | SQLite (dev/test default), MySQL/Postgres configurable for production |
| Tests | Pest 4 |
| Formatter | Laravel Pint 1 |
| Static analysis | Larastan 3 (PHPStan) |
| Dev trace inspector | Laravel Pail 1 |
| Local server | Laravel Herd |

---

## Requirements

- **PHP 8.5+** with the typical Laravel extensions (mbstring, openssl, pdo, tokenizer, etc.)
- **Composer**
- **Laravel Herd** (recommended on macOS) — or any environment that can serve a Laravel app (`php artisan serve`, Valet, Sail, Docker, etc.)
- **SQLite** for the default dev/test setup, or configure MySQL/Postgres in `.env`

---

## Installation

```bash
git clone <repo-url> organizer
cd organizer
composer setup
```

`composer setup` runs:

1. `composer install`
2. copies `.env.example` → `.env` if missing
3. `php artisan key:generate`
4. `php artisan migrate --force`

The default `.env.example` ships with `APP_NAME=Organizer` and `APP_URL=http://organizer.test` (the Herd convention). Adjust as needed for your environment.

---

## Running the app

With **Laravel Herd** installed, the site is served automatically at:

```
https://organizer.test
```

No `php artisan serve` needed — Herd watches the project directory.

Without Herd:

```bash
php artisan serve
# serves at http://127.0.0.1:8000
```

**Health check:** `GET /up` returns 200 when the app is bootstrapped and the DB is reachable.

---

## API overview

### Base URL

All API routes are mounted at the domain root (no `/api` prefix):

```
https://organizer.test/login
https://organizer.test/user
https://organizer.test/users
```

### Authentication

Bearer tokens via Laravel Sanctum. Obtain a token by `POST /login` with `email` + `password`; include the returned `access_token` as `Authorization: Bearer <token>` on subsequent requests. Tokens expire after **2 days** (configurable in `config/sanctum.php`). On login, prior tokens for the user are revoked.

### Versioning

API versions are **header-based, not URL-based**. Send the version in the `X-API-Version` header:

```
X-API-Version: 1
```

When omitted, the highest supported version is used (currently `1`). Unsupported or non-numeric versions return `400 Problem+JSON` with the list of supported versions.

### Current endpoints

| Method | URI | Name | Auth | Throttle |
|---|---|---|---|---|
| POST | `/login` | `api.v1.auth.login` | guest | `api` + `login` |
| GET | `/user` | `api.v1.user.show` | sanctum | `api` |
| GET | `/users` | `api.v1.user.index` | sanctum | `api` |

### Error responses

Every error — across all routes — is an **RFC 9457 Problem+JSON** document with `Content-Type: application/problem+json`:

```json
{
  "title": "Too Many Requests",
  "status": 429,
  "detail": "You have exceeded the rate limit for this endpoint. Please wait before retrying.",
  "retry_after": 47,
  "trace_id": "8c6aadfb"
}
```

> **Note:** the `type` field from RFC 9457 is intentionally **omitted** from all Problem documents in this project. Do not add it back.

### Rate limiting

Every API route is wrapped in `throttle:api`:

- **Authenticated users:** 1000 requests/minute, keyed by user ID
- **Guests:** 60 requests/minute, keyed by IP

The `/login` route additionally stacks `throttle:login` (5/minute per email) for brute-force protection.

When the limit is exceeded, the `429` response includes:

- Body: `retry_after` (seconds) and `trace_id`
- Headers: `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

These headers are present on **both** successful responses and the 429, so clients can implement sensible backoff.

### Trace IDs

Every API response carries an `X-Trace-Id` header (8 hex chars, generated server-side or accepted from a client-supplied header). The same ID appears in:

- the response header `X-Trace-Id`
- every log line's context for that request
- the `trace_id` field of any Problem+JSON error body

This lets a user report an error ID and support can correlate it to a single server-side log line.

---

## Architecture

Organizer follows a **domain-sliced classic Laravel** layout. Domain folders appear at every layer; cross-cutting concerns stay flat in their conventional Laravel directories.

```
app/
├── Actions/                     # Domain use cases (HTTP-agnostic). Sliced by domain.
│   ├── Auth/LoginUserAction.php
│   └── User/...
├── Data/                        # spatie/laravel-data DTOs (API-facing). Sliced by domain.
│   └── Api/UserData.php
├── Http/
│   ├── Controllers/Api/         # Invokable, one action per class. Sliced by domain.
│   │   ├── Auth/LoginController.php
│   │   └── User/UserShowController.php
│   ├── Requests/                # FormRequest validation. Sliced by domain.
│   └── Middleware/              # HTTP middleware. Flat (cross-cutting).
├── Models/                      # Eloquent models. Flat (cross-cutting).
├── Support/                     # Cross-cutting utilities (Problem, ExceptionLogger).
│   ├── Problem.php
│   └── ExceptionLogger.php
└── Logging/                     # Custom log formatter.
```

### Naming conventions

| Layer | Pattern | Example |
|---|---|---|
| Controller (invokable) | `{Entity}{Action}Controller` | `UserShowController`, `UserIndexController` |
| Action | `{Verb}{Entity}Action` | `LoginUserAction` |
| Request | `{Entity}{Action}Request` | `LoginRequest`, `UserUpdateRequest` |
| Data | `{Entity}Data` | `UserData` |
| Middleware | `{Concern}Middleware` or descriptive noun | `ApiVersion`, `AssignTraceId` |

Controllers are always invokable (`__invoke`), one class per HTTP endpoint.

### Action vs. Controller — when to use which

A **controller** is an HTTP adapter; an **action** is a domain use case.

- **Use an Action** when the use case is invoked from a non-HTTP source (job, command, listener), mutates state, opens a transaction, or would otherwise put 5–7+ lines of orchestration in the controller.
- **Otherwise** put the one-liner directly in the controller. `UserShowController` has no action — `UserData::fromModel($request->user())` lives in the controller.

Actions must be HTTP-agnostic and reusable across controllers, jobs, commands, and other actions without modification.

### API error responses

All error responses return RFC 9457 Problem+JSON via `App\Support\Problem::response($status, $title, $detail, $extra = [])`. Exception → Problem mapping lives in `bootstrap/app.php` `->withExceptions()`. The mapping order matters (Laravel uses the first match):

1. `ValidationException` → 422
2. `AuthenticationException` → 401
3. `AuthorizationException` → 403
4. `ModelNotFoundException` → 404
5. `NotFoundHttpException` → 404
6. `ThrottleRequestsException` → 429 (with rate-limit headers preserved)
7. `HttpException` (dynamic status)
8. `Throwable` catch-all → 500 (with diagnostics in non-production, generic message in production)

---

## Conventions & gotchas

These are the non-obvious things new contributors will trip on.

### No `type` field in Problem+JSON

The `type` member from RFC 9457 is **intentionally omitted** from every Problem document. Do not uncomment the `type` line in `App\Support\Problem::response()` or add `type` assertions to tests. This is a project-wide decision.

### API versioning is header-based

Versions are expressed via the `X-API-Version` header, **never** in URLs. There are no `V1/` controller directories. Route names keep a `v1` segment only as an internal unique key (`api.v1.user.show`) — this is invisible to clients and future-proofs against route name collisions when v2 ships.

### Rate limiting

`throttle:api` is applied to the entire `api.v1.*` route group. Guests are throttled harder (60/min by IP) than authenticated users (1000/min by user ID). The `/login` route stacks `throttle:login` on top. Tune the numbers in `app/Providers/AppServiceProvider.php` (`RateLimiter::for('api', ...)`).

The `429` response is rendered as Problem+JSON with `retry_after` and `trace_id` in the body, and the `X-RateLimit-*` / `Retry-After` headers are re-attached to the response (they live on the exception and would otherwise be dropped when returning a fresh `JsonResponse`).

### Tiered exception logging

Logs are slim, single-line, scannable. **Stack traces are never written to disk.** Use `php artisan pail` in dev for trace inspection.

Logging tiers (routed in `bootstrap/app.php`, written by `App\Support\ExceptionLogger`):

| Tier | Status codes | Level |
|---|---|---|
| Server errors | 5xx + non-HTTP Throwables | `error`, always |
| Client abuse signals | 403, 422, 429 | `warning`, always |
| Other 4xx | 400, 405, 410, 415, … | `info` |
| Client noise | 401, 404, ModelNotFound | silent (not logged) |

The log file is named `organizer.log`, not `laravel.log` (configured in `config/logging.php`).

### The `stopIgnoring` + `isClientNoise` interaction

Laravel's exception handler keeps an `$internalDontReport` list that includes `HttpException`, `HttpResponseException`, `ValidationException`, and `AuthorizationException`. The filter gate uses `instanceof`, so **any subclass** is also filtered. `stopIgnoring()` in `bootstrap/app.php` un-ignores these families so they reach the tiered `report` callback.

If you add a new `HttpResponseException` subclass and want it **logged**, carve it out in `App\Support\ExceptionLogger::isClientNoise()` (return `false` for it). Do **not** add it to the `CLIENT_NOISE` array — that array uses `instanceof` and would re-silence `ThrottleRequestsException` as a side effect.

### Trace IDs

`App\Http\Middleware\AssignTraceId` runs in the `api` middleware group before auth. It generates 8 hex chars per request (or accepts a client `X-Trace-Id` header), stores the value on the `app.trace_id` container binding, and echoes it back via the `X-Trace-Id` response header. It is included in every log line's context and in the Problem+JSON body.

### Sanctum token expiration

Global token expiration is configured in `config/sanctum.php` (`expiration` option), currently set to **2 days**. `$user->createToken($name)` (no third arg) inherits the global expiration. On login, prior tokens for the user are revoked before a new one is created. Expired tokens return `401 Problem+JSON` — there is no auto-refresh; the client must re-authenticate.

---

## Development workflow

| Task | Command |
|---|---|
| Format PHP (fix) | `composer lint` or `vendor/bin/pint --parallel` |
| Format PHP (check only) | `composer lint:check` |
| Static analysis | `composer types:check` |
| Run everything (lint + types + tests) | `composer test` |
| CI check | `composer ci:check` |
| Run tests only | `php artisan test --compact` |
| Filter tests | `php artisan test --compact --filter=TestName` |
| Live trace inspector | `php artisan pail` |
| Tinker | `php artisan tinker` |

> **Per project convention:** after editing PHP files, run `vendor/bin/pint --dirty --format agent` before finalizing changes. Do not run `--test` mode; run the formatter directly.

---

## Testing

Tests use **Pest 4**. The `tests/Feature` directory uses `RefreshDatabase` (wired in `tests/Pest.php`) and runs against SQLite `:memory:` (configured in `phpunit.xml`).

### Layout

```
tests/
├── Feature/               # Feature tests (HTTP-level)
│   ├── AuthLoginTest.php
│   ├── CurrentUserTest.php
│   ├── ProblemJsonTest.php
│   ├── RateLimitTest.php
│   ├── ExceptionLoggerTest.php
│   └── Api/V1/            # v1-specific feature tests
└── Unit/                  # Unit tests
```

### Conventions

- Create feature tests with `php artisan make:test --pest SomeFeatureTest` (do **not** include the suite directory in the name).
- Use factories for models; do not create models in tinker.
- Mock `RateLimiter` with `shouldReceive('limiter')` (returns the named-limiter closure) **plus** `shouldReceive('tooManyAttempts')` / `shouldReceive('availableIn')` when testing throttle paths — named limiters resolve the closure first.
- Never delete tests without approval.

### Running

```bash
php artisan test --compact                          # all
php artisan test --compact --filter=RateLimitTest   # one file
php artisan test --compact --filter='429 throttle'  # one test by name
```

---

## Project structure

```
organizer/
├── app/
│   ├── Actions/              # Domain use cases (HTTP-agnostic)
│   ├── Data/                 # spatie/laravel-data DTOs (API boundary)
│   ├── Http/
│   │   ├── Controllers/Api/  # Invokable controllers, one per endpoint
│   │   ├── Middleware/       # ApiVersion, AssignTraceId, HttpSunset, RequireApiVersion
│   │   └── Requests/         # FormRequest validation
│   ├── Logging/              # SlimLineFormatter (slim one-line log format)
│   ├── Models/               # Eloquent models
│   ├── Providers/            # AppServiceProvider (rate limiter definitions)
│   └── Support/              # Problem, ExceptionLogger (cross-cutting utilities)
├── bootstrap/
│   └── app.php               # Exception renderers/reporters, middleware wiring
├── config/
│   ├── app.php               # APP_NAME=Organizer
│   ├── sanctum.php           # Token expiration (2 days)
│   └── logging.php           # organizer.log, slim formatter
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Schema
│   └── seeders/              # Seeders
├── routes/
│   ├── api.php               # API routes (api.v1.* group, throttle:api)
│   ├── web.php               # Web routes (minimal — API-only project)
│   └── console.php           # Console commands
├── tests/
│   ├── Feature/              # Pest feature tests
│   └── Unit/                 # Pest unit tests
├── composer.json             # uchm4n/organizer
├── phpunit.xml               # Test config (sqlite :memory:)
└── README.md                 # This file
```

---

## Extending the API

To add a new endpoint (e.g. `GET /notes`):

1. **Controller** — `php artisan make:controller Api/Note/NoteIndexController --invokable`
2. **Action** (if the use case has side effects / persistence / >5–7 lines) — `php artisan make:class Actions/Note/IndexNotesAction`
3. **Form request** (if input validation needed) — `php artisan make:request Note/NoteIndexRequest`
4. **Data resource** (for the response payload) — create `app/Data/Api/NoteData.php` extending spatie/laravel-data
5. **Route** — add to `routes/api.php` inside the `api.v1.*` group (inherits `throttle:api`):
   ```php
   Route::middleware('auth:sanctum')->group(function (): void {
       Route::get('/notes', NoteIndexController::class)->name('note.index');
   });
   ```
6. **Test** — `php artisan make:test --pest NoteIndexTest`
7. **Format** — `vendor/bin/pint --dirty --format agent`
8. **Verify** — `php artisan test --compact --filter=NoteIndexTest`

Use `php artisan route:list` to confirm the route is registered with the expected middleware.

---

## Deployment

Organizer can be deployed using [Laravel Cloud](https://cloud.laravel.com/) — the fastest way to deploy and scale a production Laravel app.

Standard deployment steps:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

In production, the `Throwable` catch-all in `bootstrap/app.php` returns only a generic `"An unexpected error occurred. Please try again later."` message — internal details (exception class, file, line) never leak. Diagnostics are only exposed in non-production environments.

---

## Contributing & code style

- **Style:** enforced by Laravel Pint. Run `vendor/bin/pint --dirty --format agent` after editing PHP.
- **Types:** Larastan (PHPStan) via `composer types:check`.
- **Tests:** every change must be programmatically tested. Write a new test or update an existing one, then run the affected tests.
- **Guidelines:** see `AGENTS.md` in the repo root for the full, curated conventions (architecture, error handling, logging, Sanctum, API versioning, and more).
- **No PR without green tests:** `composer test` must pass before merging.

---

## License

Organizer is open-sourced software licensed under the [MIT license](LICENSE).
