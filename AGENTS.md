<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Do NOT
- Do not run migrations or seeders without user approval.
- Do not uncomment `'type' => 'https://httpstatuses.com/'.$status,` this line in `App\Support\Problem::response()` unless the user explicitly requests it.


=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== project conventions ===

# Architecture Conventions

## Directory Structure (Domain-Sliced Classic Laravel)

The application follows the **domain-sliced classic Laravel** layout. Domain folders appear consistently at every layer; cross-cutting concerns stay flat in their conventional Laravel directories.

```
app/
├── Actions/                     # Domain use cases (HTTP-agnostic). Sliced by domain.
│   ├── Auth/LoginUserAction.php
│   ├── User/UpdateUserProfileAction.php
│   └── Billing/CreateSubscriptionAction.php
├── Data/                        # spatie/laravel-data DTOs (API-facing). Sliced by domain.
│   ├── Auth/
│   ├── User/UserData.php
│   └── Billing/
├── Http/
│   ├── Controllers/Api/         # Invokable, one action per class. Sliced by domain.
│   │   ├── Auth/LoginController.php
│   │   ├── User/UserShowController.php
│   │   └── Billing/...
│   ├── Requests/                # FormRequest validation. Sliced by domain.
│   │   ├── Auth/LoginRequest.php
│   │   └── User/UpdateUserRequest.php
│   └── Middleware/             # HTTP middleware. Flat (cross-cutting).
├── Models/                     # Eloquent models. Flat (cross-cutting).
├── Policies/, Events/, Listeners/, Jobs/, ...  # other Laravel-default dirs; slice by domain inside each
└── Support/                    # Cross-cutting utilities (Problem, helpers, value-object bases).
                                 # Never put domain logic here.
```

Rules:
- One folder per **bounded domain** (Auth, User, Billing, Subscription, Invoice…), not per HTTP resource.
- If two domains share 80% of types they are probably one domain. If a domain has more than ~12 controllers it is likely two domains.
- Never version-slice folders. API versioning is expressed via the `X-API-Version` header (see below), not via `V1/` directories.

## Naming Conventions

| Layer | Pattern | Examples |
|---|---|---|
| Controller (invokable) | `{Entity}{Action}Controller` | `UserShowController`, `UserUpdateController`, `SubscriptionCreateController` |
| Action | `{Verb}{Entity}Action` | `LoginUserAction`, `UpdateUserProfileAction`, `CreateSubscriptionAction` |
| Request | `{Entity}{Action}Request` | `LoginRequest`, `UserUpdateRequest`, `SubscriptionCreateRequest` |
| Data | `{Entity}Data` (single), `{Entity}Collection` (list), `Paginated{Entity}Data` (paginated) | `UserData`, `UserCollection` |
| Middleware | `{Concern}Middleware` OR descriptive noun (`ApiVersion`, `HttpSunset`, `EnsureTokenIsValid`) | — |

Controllers are always invokable (`__invoke`), one class per HTTP endpoint.

## Action vs. Controller — when to use which

Controllers and Actions are NOT the same. A controller is an HTTP adapter; an action is a domain use case.

- **Controller** is bound to the HTTP request. Its job: receive a validated `FormRequest`, hand clean primitives / DTOs / domain objects to the action, and format the HTTP-shaped response (or return a Data object).
- **Action** is HTTP-agnostic. It accepts primitives/DTOs, performs the business use case (persistence, side effects, transactions, domain rules), and returns domain objects (`NewAccessToken`, `User`, `bool`, `void`). Actions MUST be reuseable across controllers, jobs, commands, listeners, and other actions without modification.

Create an Action when **any** of the following are true:
1. The use case is invoked from a non-HTTP source (job, command, listener, another action, test helper).
2. The logic mutates state, has side effects, opens a transaction, or enforces domain rules — beyond "fetch row → transform".
3. The controller would otherwise have more than ~5–7 lines of orchestration that isn't pure request/response plumbing.

Otherwise: put the one-liner directly in the controller. No action class. (Example: `UserShowController` has no action — `UserData::fromModel($request->user())` lives in the controller.)

## API Error Responses (RFC 9457 Problem+JSON)

All error responses — across web and API routes — return RFC 9457 Problem+JSON documents, never HTML.

- Helpers: `App\Support\Problem::response(int $status, string $title, string $detail, array $extra = []): JsonResponse` and `App\Support\Problem::titleForStatus(int $status): string`.
- Every error response has `Content-Type: application/problem+json` and the body shape `{type,title,status,detail,*}` where `type` is `https://httpstatuses.com/{status}`.
- Exception → Problem mapping lives in `bootstrap/app.php` `->withExceptions()` and is registered in this order (Laravel uses the first match): `ValidationException` → 422, `AuthenticationException` → 401, `AuthorizationException` → 403, `ModelNotFoundException` → 404, `NotFoundHttpException` → 404, `HttpException` (dynamic), then `Throwable` catch-all.
- In non-production environments, the `Throwable` catch-all exposes `exception`, `file`, `line` extension members for debugging. In production, it returns only a generic `"An unexpected error occurred. Please try again later."` — internal details never leak.

## API Versioning (Header-Based)

API versions are NOT expressed in URLs. The version is selected via the `X-API-Version` request header.

- Middleware: `App\Http\Middleware\ApiVersion`, prepended to the `api` middleware group in `bootstrap/app.php`, so it runs before any authentication middleware.
- Default: when `X-API-Version` is omitted, the highest version in `ApiVersion::SUPPORTED` is used. Today that is `1`.
- Unsupported / non-numeric versions return a `400 Problem+Json` with the supported version list.
- Route names keep the `v1` segment as an internal unique key (`api.v1.user.show`); this is invisible to clients and future-proofs against route name collisions when v2 ships.
- When v2 needs to ship: split `routes/api.php` into `routes/api/v1.php` and `routes/api/v2.php`, load both under an internal prefix, and let `ApiVersion` middleware resolve which controller handles the matched URI based on the header.

## Sanctum Token Expiration

- Global token expiration (minutes) is configured in `config/sanctum.php` via the `expiration` option. Set to `1440` (1 day).
- `$user->createToken($name)` (no third arg) inherits the global expiration. Passing a third arg overrides it — avoid unless intentional.
- On login, prior tokens for the user are revoked: `$user->tokens()->delete()` before `createToken(...)`.
- Expired tokens return `401 Problem+Json` (no auto-refresh; the client must re-authenticate).
- Scheduled cleanup of expired rows: `Schedule::command('sanctum:prune-expired --hours=24')->daily();` (optional, add when needed).

</laravel-boost-guidelines>
