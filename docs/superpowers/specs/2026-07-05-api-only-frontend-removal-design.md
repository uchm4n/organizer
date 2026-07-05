# API-Only Frontend Removal Design

## Goal

Refactor the application from a minimal Inertia / Vue starter into a backend-first Laravel API that keeps only a plain Blade welcome page at `/` and preserves Blade / Markdown rendering for future emails.

## Current State

- `routes/web.php` uses `Route::inertia('/', 'Welcome')` for the only web page.
- `bootstrap/app.php` appends `App\Http\Middleware\HandleInertiaRequests` and `Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets` to the `web` middleware stack.
- `resources/views/app.blade.php`, `resources/js/app.ts`, `resources/js/pages/Welcome.vue`, `resources/css/app.css`, and `vite.config.ts` exist only to support that welcome page.
- `resources/js/actions/**`, `resources/js/routes/**`, and `resources/js/wayfinder/**` are generated frontend helpers with no current value in an API-only application.
- `composer.json` still requires `inertiajs/inertia-laravel` and `laravel/wayfinder` and still assumes Node-based setup / CI steps.
- The real application surface already lives in `routes/api.php`, API controllers, form requests, actions, and API feature tests.

## Decision

Adopt a plain Blade welcome page and remove the Inertia / Vue / Vite / Wayfinder frontend scaffolding.

The view layer stays in place because it is still the correct foundation for:

- a simple HTML landing page at `/`
- future Blade mail views
- future Markdown mailables generated with Laravel's mail tooling

Token-based API behavior stays unchanged. This refactor is about removing the web asset stack, not redesigning authentication.

## Scope

### In Scope

- Replace the Inertia home route with a normal Blade response.
- Create a self-contained `resources/views/welcome.blade.php` page with no JS or asset pipeline dependency.
- Remove Inertia middleware and config.
- Remove Vue / Vite / Tailwind / Wayfinder frontend source files and generated route helpers.
- Remove Node package manifests and frontend build / lint / typecheck scripts that only existed for the deleted SPA stack.
- Remove Composer packages that are only needed for the deleted frontend stack after confirming no remaining PHP usage.
- Update tests so the home-page smoke test matches the new Blade response.
- Remove `.env.example` values that only exist for the deleted frontend pipeline.

### Out of Scope

- Redesigning API routes, controllers, or response shapes.
- Changing Sanctum token issuance or bearer-token authentication.
- Simplifying `config/auth.php`, `config/sanctum.php`, or `config/session.php` just because they still reflect starter defaults.
- Adding actual mailables or email templates in this refactor.
- Renaming the package or changing starter-kit metadata unrelated to runtime behavior.
- Cleaning up editor / agent configuration that does not affect application runtime.

## Architecture

### Web Surface

- Keep `routes/web.php` only for the home page.
- Change the route to `Route::view('/', 'welcome')->name('home');` or an equivalent plain Blade route.
- Render static HTML directly from Blade.

### API Surface

- Keep `routes/api.php` unchanged.
- Keep the JSON exception rendering rule in `bootstrap/app.php` unchanged for `api/*` requests.
- Keep the existing login and authenticated user endpoints untouched.

### Views and Mail

- Keep `resources/views/` as the shared server-rendered template location.
- The new `welcome.blade.php` becomes the only browser-facing page.
- Future email templates continue to fit Laravel's normal Blade / Markdown mail flow under `resources/views/mail/**`.

### Tooling

- The repository should no longer require Node, npm, Vite, Vue, Tailwind, or generated TypeScript route helpers.
- Setup, CI, and tests should become PHP / Composer only.

## File-Level Plan

### Create

- `resources/views/welcome.blade.php`
  - Minimal HTML page.
  - No `@vite`, `@fonts`, or Inertia components.
  - Safe to keep simple inline CSS for readability if desired.

### Modify

- `routes/web.php`
  - Replace `Route::inertia('/', 'Welcome')` with a plain Blade route.

- `bootstrap/app.php`
  - Remove `HandleInertiaRequests` and `AddLinkHeadersForPreloadedAssets` imports.
  - Remove the appended Inertia-specific web middleware.
  - Keep web routing, API routing, the API prefix, and JSON exception rules.

- `composer.json`
  - Remove `inertiajs/inertia-laravel`.
  - Remove `laravel/wayfinder` if no PHP-side usage remains.
  - Remove `npm install`, `npm run build`, and any npm-based CI checks.
  - Keep PHP linting, PHPStan, and test scripts.

- `.env.example`
  - Remove `VITE_APP_NAME`.

- `tests/Feature/ExampleTest.php`
  - Keep it as the smoke test for the home page, but assert against the Blade response.

### Delete

- `app/Http/Middleware/HandleInertiaRequests.php`
- `config/inertia.php`
- `resources/views/app.blade.php`
- `resources/js/**`
- `resources/css/app.css`
- `vite.config.ts`
- `package.json`
- `package-lock.json`
- `eslint.config.js`
- `tsconfig.json`

## Welcome Page Behavior

The new `/` page should be deliberately minimal:

- return HTTP 200
- identify the project as an API
- optionally show the application name from config
- require no compiled CSS, JavaScript, or shared request props

The page should not depend on:

- Inertia shared props
- Vite manifest entries
- Vue components
- frontend routing helpers

## Dependency Rules

`inertiajs/inertia-laravel` becomes removable once all of the following are gone:

- `Route::inertia(...)`
- Inertia middleware
- Inertia Blade components
- Inertia config

`laravel/wayfinder` becomes removable only after confirming there is no remaining PHP-side usage outside deleted frontend files. If the search stays clean, remove it in the same refactor instead of leaving dead dependencies behind.

## Risks and Non-Goals

- `bootstrap/cache/*` and `storage/framework/views/*` may still contain stale references locally after the refactor. They should be regenerated naturally rather than edited by hand.
- Removing Node-based scripts changes developer and CI expectations, so verification must prove the project still installs and tests cleanly without npm.
- Session and Sanctum config may still look SPA-oriented afterward. That is acceptable in this refactor because changing those files would expand scope into authentication behavior.

## Testing Strategy

Minimum verification for the refactor:

- Home page feature test passes against the Blade welcome page.
- Existing API login tests still pass.
- Existing authenticated current-user API tests still pass.

Suggested command set:

- `php artisan test --compact tests/Feature/ExampleTest.php tests/Feature/AuthLoginTest.php tests/Feature/Api/V1/CurrentUserTest.php`
- `vendor/bin/pint --dirty --format agent`

## Acceptance Criteria

- `GET /` renders successfully without Inertia, Vue, Vite, or compiled frontend assets.
- The repository no longer requires Node / npm for setup or CI.
- No runtime application file references `Route::inertia`, `HandleInertiaRequests`, Inertia Blade components, `@vite`, or Wayfinder-generated frontend artifacts.
- API login and authenticated user flows continue to pass unchanged.
- Blade remains available for future mail views and Markdown mailables.
