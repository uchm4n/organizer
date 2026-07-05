# API-Only Frontend Removal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the Inertia / Vue / Vite / Wayfinder frontend starter stack, keep a plain Blade welcome page at `/`, and leave the Laravel API and future Blade / Markdown mail rendering intact.

**Architecture:** The web surface becomes a single Blade-rendered `welcome` view served from `routes/web.php`. API routes, token authentication, and JSON exception rendering stay unchanged. Repository tooling becomes PHP / Composer only, with Node-specific assets, configs, and CI steps removed.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, Sanctum, Blade views, Composer, GitHub Actions, Pint, PHPStan.

---

## File Map

- `routes/web.php`
  - Defines the only web route and switches `/` from Inertia to Blade.
- `resources/views/welcome.blade.php`
  - New static landing page for the API.
- `tests/Feature/ExampleTest.php`
  - Home-page behavior regression for the Blade welcome page.
- `tests/Feature/ApiOnlyProjectStructureTest.php`
  - Project-shape regression that guards the removal of Inertia and Node/Vite tooling.
- `bootstrap/app.php`
  - Drops Inertia-specific web middleware while preserving API behavior.
- `composer.json`
  - Removes frontend-oriented packages and npm-driven scripts; keeps PHP-only setup / test / lint flows.
- `composer.lock`
  - Records removal of `inertiajs/inertia-laravel` and `laravel/wayfinder`.
- `.env.example`
  - Removes the unused `VITE_APP_NAME` environment variable.
- `.github/workflows/tests.yml`
  - Removes Node setup and asset build steps from the test matrix.
- `.github/workflows/lint.yml`
  - Becomes PHP-only linting.
- `app/Http/Middleware/HandleInertiaRequests.php`
  - Deleted because no Inertia responses remain.
- `config/inertia.php`
  - Deleted because Inertia is removed.
- `resources/views/app.blade.php`
  - Deleted because the Inertia root shell is no longer used.
- `resources/css/app.css`
  - Deleted with the Tailwind / Vite stack.
- `resources/js/app.ts`
  - Deleted with the Inertia bootstrap.
- `resources/js/lib/utils.ts`
  - Deleted with the frontend source tree.
- `resources/js/pages/Welcome.vue`
  - Deleted because `/` moves to Blade.
- `resources/js/types/auth.ts`
  - Deleted with the frontend source tree.
- `resources/js/types/global.d.ts`
  - Deleted with the frontend source tree.
- `resources/js/types/index.ts`
  - Deleted with the frontend source tree.
- `resources/js/types/vue-shims.d.ts`
  - Deleted with the frontend source tree.
- `package.json`
  - Deleted because Node tooling is removed.
- `package-lock.json`
  - Deleted because Node tooling is removed.
- `vite.config.ts`
  - Deleted because Vite is removed.
- `eslint.config.js`
  - Deleted because frontend linting is removed.
- `tsconfig.json`
  - Deleted because TypeScript is removed.
- `pnpm-workspace.yaml`
  - Deleted because the Node workspace is removed.
- `.npmrc`
  - Deleted because npm is removed.
- `.prettierrc`
  - Deleted because Prettier is removed.
- `.prettierignore`
  - Deleted because Prettier is removed.

### Task 1: Replace the Inertia Welcome Page With Blade

**Files:**
- Create: `resources/views/welcome.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/ExampleTest.php`
- Test: `tests/Feature/ExampleTest.php`

- [ ] **Step 1: Write the failing home-page regression**

```php
<?php

test('the welcome page is rendered as a plain blade api landing page', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee(config('app.name'))
        ->assertSee('stateless JSON API')
        ->assertDontSee('data-page', false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Feature/ExampleTest.php`

Expected: FAIL because the current Inertia page does not contain `stateless JSON API` and still renders an Inertia `data-page` payload.

- [ ] **Step 3: Write the minimal Blade implementation**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
```

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
    </head>
    <body style="margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a;">
        <main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
            <section style="width: 100%; max-width: 42rem; border: 1px solid #e2e8f0; border-radius: 1rem; background: #ffffff; padding: 2rem; box-sizing: border-box;">
                <p style="margin: 0; font-size: 0.875rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                    API
                </p>

                <h1 style="margin: 0.75rem 0 0; font-size: 2rem;">
                    {{ config('app.name') }}
                </h1>

                <p style="margin: 1rem 0 0; line-height: 1.6; color: #475569;">
                    This application serves a stateless JSON API. Blade remains available for future email templates and other server-rendered messages.
                </p>
            </section>
        </main>
    </body>
</html>
```

- [ ] **Step 4: Run the home-page test again**

Run: `php artisan test --compact tests/Feature/ExampleTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the welcome-page switch**

```bash
git add routes/web.php resources/views/welcome.blade.php tests/Feature/ExampleTest.php
git commit -m "refactor: replace inertia welcome page"
```

### Task 2: Remove Inertia Runtime Integration

**Files:**
- Create: `tests/Feature/ApiOnlyProjectStructureTest.php`
- Modify: `bootstrap/app.php`
- Delete: `app/Http/Middleware/HandleInertiaRequests.php`
- Delete: `config/inertia.php`
- Delete: `resources/views/app.blade.php`
- Test: `tests/Feature/ApiOnlyProjectStructureTest.php`
- Test: `tests/Feature/ExampleTest.php`

- [ ] **Step 1: Write the failing Inertia-removal regression**

```php
<?php

test('the application bootstrap is free of inertia web integration', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->not->toContain('HandleInertiaRequests')
        ->not->toContain('AddLinkHeadersForPreloadedAssets');

    expect(file_exists(app_path('Http/Middleware/HandleInertiaRequests.php')))->toBeFalse();
    expect(file_exists(config_path('inertia.php')))->toBeFalse();
    expect(file_exists(resource_path('views/app.blade.php')))->toBeFalse();
});
```

- [ ] **Step 2: Run the structure test to verify it fails**

Run: `php artisan test --compact tests/Feature/ApiOnlyProjectStructureTest.php`

Expected: FAIL because `bootstrap/app.php` still references Inertia middleware and the Inertia files still exist.

- [ ] **Step 3: Remove the Inertia-specific runtime wiring**

```php
<?php

use App\Http\Middleware\HttpSunset;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'sunset' => HttpSunset::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

```bash
rm -f app/Http/Middleware/HandleInertiaRequests.php config/inertia.php resources/views/app.blade.php
```

- [ ] **Step 4: Run the structure and home-page tests again**

Run: `php artisan test --compact tests/Feature/ApiOnlyProjectStructureTest.php tests/Feature/ExampleTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the Inertia runtime removal**

```bash
git add bootstrap/app.php tests/Feature/ApiOnlyProjectStructureTest.php
git add -u app/Http/Middleware/HandleInertiaRequests.php config/inertia.php resources/views/app.blade.php
git commit -m "refactor: remove inertia runtime integration"
```

### Task 3: Remove Frontend Tooling, Dependencies, and CI Assumptions

**Files:**
- Modify: `tests/Feature/ApiOnlyProjectStructureTest.php`
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `.env.example`
- Modify: `.github/workflows/tests.yml`
- Modify: `.github/workflows/lint.yml`
- Delete: `resources/css/app.css`
- Delete: `resources/js/app.ts`
- Delete: `resources/js/lib/utils.ts`
- Delete: `resources/js/pages/Welcome.vue`
- Delete: `resources/js/types/auth.ts`
- Delete: `resources/js/types/global.d.ts`
- Delete: `resources/js/types/index.ts`
- Delete: `resources/js/types/vue-shims.d.ts`
- Delete: `package.json`
- Delete: `package-lock.json`
- Delete: `vite.config.ts`
- Delete: `eslint.config.js`
- Delete: `tsconfig.json`
- Delete: `pnpm-workspace.yaml`
- Delete: `.npmrc`
- Delete: `.prettierrc`
- Delete: `.prettierignore`
- Test: `tests/Feature/ApiOnlyProjectStructureTest.php`

- [ ] **Step 1: Extend the structure test with a failing tooling-removal regression**

```php
<?php

test('the application bootstrap is free of inertia web integration', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->not->toContain('HandleInertiaRequests')
        ->not->toContain('AddLinkHeadersForPreloadedAssets');

    expect(file_exists(app_path('Http/Middleware/HandleInertiaRequests.php')))->toBeFalse();
    expect(file_exists(config_path('inertia.php')))->toBeFalse();
    expect(file_exists(resource_path('views/app.blade.php')))->toBeFalse();
});

test('the repository no longer ships node based frontend tooling', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
    $envExample = file_get_contents(base_path('.env.example'));
    $testsWorkflow = file_get_contents(base_path('.github/workflows/tests.yml'));
    $lintWorkflow = file_get_contents(base_path('.github/workflows/lint.yml'));

    expect(array_key_exists('inertiajs/inertia-laravel', $composer['require']))->toBeFalse();
    expect(array_key_exists('laravel/wayfinder', $composer['require']))->toBeFalse();
    expect(array_key_exists('dev', $composer['scripts']))->toBeFalse();
    expect(collect($composer['scripts']['setup'])->contains('npm install'))->toBeFalse();
    expect(collect($composer['scripts']['setup'])->contains('npm run build'))->toBeFalse();
    expect(collect($composer['scripts']['ci:check'])->contains('npm run lint:check'))->toBeFalse();
    expect(collect($composer['scripts']['ci:check'])->contains('npm run format:check'))->toBeFalse();
    expect(collect($lock['packages'])->pluck('name')->contains('inertiajs/inertia-laravel'))->toBeFalse();
    expect(collect($lock['packages'])->pluck('name')->contains('laravel/wayfinder'))->toBeFalse();

    expect($envExample)->not->toContain('VITE_APP_NAME');

    expect(file_exists(base_path('package.json')))->toBeFalse();
    expect(file_exists(base_path('package-lock.json')))->toBeFalse();
    expect(file_exists(base_path('vite.config.ts')))->toBeFalse();
    expect(file_exists(base_path('eslint.config.js')))->toBeFalse();
    expect(file_exists(base_path('tsconfig.json')))->toBeFalse();
    expect(file_exists(base_path('pnpm-workspace.yaml')))->toBeFalse();
    expect(file_exists(base_path('.npmrc')))->toBeFalse();
    expect(file_exists(base_path('.prettierrc')))->toBeFalse();
    expect(file_exists(base_path('.prettierignore')))->toBeFalse();
    expect(file_exists(resource_path('css/app.css')))->toBeFalse();
    expect(file_exists(resource_path('js/app.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/lib/utils.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/pages/Welcome.vue')))->toBeFalse();
    expect(file_exists(resource_path('js/types/auth.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/global.d.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/index.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/vue-shims.d.ts')))->toBeFalse();

    expect($testsWorkflow)
        ->not->toContain('setup-node')
        ->not->toContain('npm ')
        ->not->toContain('Build Assets');

    expect($lintWorkflow)
        ->not->toContain('npm ')
        ->not->toContain('Format Frontend')
        ->not->toContain('Lint Frontend');
});
```

- [ ] **Step 2: Run the structure test to verify it fails**

Run: `php artisan test --compact tests/Feature/ApiOnlyProjectStructureTest.php`

Expected: FAIL because Composer still requires Inertia / Wayfinder, npm files still exist, and CI still installs Node.

- [ ] **Step 3: Remove the PHP packages and tracked frontend files**

```bash
composer remove inertiajs/inertia-laravel laravel/wayfinder --no-interaction
rm -rf resources/js
rm -f resources/css/app.css package.json package-lock.json vite.config.ts eslint.config.js tsconfig.json pnpm-workspace.yaml .npmrc .prettierrc .prettierignore
```

- [ ] **Step 4: Update Composer scripts, environment defaults, and GitHub Actions**

```json
// composer.json - replace the require block with this frontend-free set
"require": {
    "php": "^8.5",
    "laravel/framework": "^13.17",
    "laravel/sanctum": "^4.0",
    "laravel/tinker": "^3.0",
    "spatie/laravel-data": "^4.23"
}
```

```json
// composer.json - replace the scripts block with this PHP-only version
"scripts": {
    "setup": [
        "composer install",
        "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
        "@php artisan key:generate",
        "@php artisan migrate --force"
    ],
    "lint": [
        "pint --parallel"
    ],
    "lint:check": [
        "pint --parallel --test"
    ],
    "ci:check": [
        "Composer\\Config::disableProcessTimeout",
        "@test"
    ],
    "types:check": [
        "phpstan analyse"
    ],
    "test": [
        "@php artisan config:clear --ansi",
        "@lint:check",
        "@types:check",
        "@php artisan test"
    ],
    "post-autoload-dump": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan package:discover --ansi"
    ],
    "post-update-cmd": [
        "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
        "@php artisan boost:update --ansi"
    ],
    "post-root-package-install": [
        "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ],
    "post-create-project-cmd": [
        "@php artisan key:generate --ansi",
        "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
        "@php artisan migrate --graceful --ansi"
    ],
    "pre-package-uninstall": [
        "Illuminate\\Foundation\\ComposerScripts::prePackageUninstall"
    ]
}
```

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

```yaml
name: tests

on:
  push:
    branches:
      - develop
      - main
      - master
      - workos
  pull_request:
    branches:
      - develop
      - main
      - master
      - workos

permissions:
  contents: read

jobs:
  ci:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: ['8.3', '8.4', '8.5']

    steps:
      - name: Checkout code
        uses: actions/checkout@9c091bb21b7c1c1d1991bb908d89e4e9dddfe3e0 # v7.0.0
        with:
          persist-credentials: false

      - name: Setup PHP
        uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # v2
        with:
          php-version: ${{ matrix.php-version }}
          tools: composer:v2
          coverage: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Copy Environment File
        run: cp .env.example .env

      - name: Generate Application Key
        run: php artisan key:generate

      - name: Run Type Analysis
        run: composer types:check

      - name: Tests
        run: php artisan test
```

```yaml
name: linter

on:
  push:
    branches:
      - develop
      - main
      - master
      - workos
  pull_request:
    branches:
      - develop
      - main
      - master
      - workos

permissions:
  contents: write

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@9c091bb21b7c1c1d1991bb908d89e4e9dddfe3e0 # v7.0.0
        with:
          persist-credentials: false

      - name: Setup PHP
        uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # v2
        with:
          php-version: '8.4'

      - name: Install Dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

      - name: Run Pint
        run: composer lint:check
```

- [ ] **Step 5: Run the structure test again**

Run: `php artisan test --compact tests/Feature/ApiOnlyProjectStructureTest.php`

Expected: PASS.

- [ ] **Step 6: Commit the tooling cleanup**

```bash
git add composer.json composer.lock .env.example .github/workflows/tests.yml .github/workflows/lint.yml tests/Feature/ApiOnlyProjectStructureTest.php
git add -u resources/css/app.css resources/js package.json package-lock.json vite.config.ts eslint.config.js tsconfig.json pnpm-workspace.yaml .npmrc .prettierrc .prettierignore
git commit -m "refactor: remove frontend tooling stack"
```

### Task 4: Run Final Verification for the API-Only Baseline

**Files:**
- Test: `tests/Feature/ExampleTest.php`
- Test: `tests/Feature/ApiOnlyProjectStructureTest.php`
- Test: `tests/Feature/AuthLoginTest.php`
- Test: `tests/Feature/Api/V1/CurrentUserTest.php`

- [ ] **Step 1: Format any changed PHP files**

Run: `vendor/bin/pint --dirty --format agent`

Expected: PASS, with no remaining formatting changes after the command completes.

- [ ] **Step 2: Run the focused regression suite**

Run: `php artisan test --compact tests/Feature/ExampleTest.php tests/Feature/ApiOnlyProjectStructureTest.php tests/Feature/AuthLoginTest.php tests/Feature/Api/V1/CurrentUserTest.php`

Expected: PASS with all selected tests green.

- [ ] **Step 3: Run the PHP-only lint and static analysis scripts**

Run: `composer lint:check && composer types:check`

Expected: PASS without invoking npm, Node, Vite, or frontend tooling.

- [ ] **Step 4: Run the end-to-end Composer test script**

Run: `composer test`

Expected: PASS and the script output should stay fully PHP / Composer based.

- [ ] **Step 5: Commit the verified API-only baseline**

```bash
git add .
git commit -m "refactor: convert starter kit to api-only app"
```
