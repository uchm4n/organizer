---
name: laravel-data-api-first
description: Use when building or refactoring Laravel JSON APIs with spatie/laravel-data. Trigger for creating app/Data/*Data.php classes, injecting Data into API controllers, converting models, collections, or paginators into typed JSON payloads, replacing manual response()->json() arrays or raw model responses, and using validation attributes, casts, transformers, nesting, collections, or lazy properties at the API boundary.
license: MIT
metadata:
  author: opencode
---

# Laravel Data API First

Use this skill when a Laravel JSON API endpoint should use `spatie/laravel-data` as its request and response boundary.

## When to Apply

Apply this skill when:

- Building or refactoring routes in `routes/api.php`
- Creating request DTOs in `app/Data/**`
- Returning typed API payloads instead of raw models or manual arrays
- Mapping models, nested relations, collections, or paginators into API JSON
- Using validation attributes, casts, transformers, or lazy properties for API responses

Do not use this skill for:

- Inertia page props unless the endpoint is still an API boundary
- General Laravel architecture, query tuning, or authorization design by itself
- Pest syntax and test-writing mechanics

Use `laravel-best-practices` for broader Laravel backend decisions and `pest-testing` for test patterns.

## Repo Defaults

This repo already has `spatie/laravel-data:^4.23` installed and published.

- Put data classes in `app/Data`
- Keep the generated `Data` suffix
- Return wrapped JSON by default: `config/data.php` has `'wrap' => 'data'`
- Dates serialize with `DATE_ATOM`
- Validation currently runs automatically only for request-based creation: `ValidationStrategy::OnlyRequests`
- `FormRequestNormalizer` is disabled, so `Data::from($formRequest)` is not the repo default
- Structure caching is enabled for `app/Data`

### Directory Convention

Prefer these namespaces unless there is a strong reason not to:

- API response and request DTOs: `App\Data\Api\...`
- Nest related DTOs by domain or endpoint family, not by controller filename
- Keep DTOs unversioned unless payloads truly diverge across API versions

## Core Rule

For new JSON API endpoints, use `laravel-data` at the HTTP boundary.

- Request input: inject a `Data` object into the controller when the endpoint accepts JSON input
- Response output: return a `Data` object or `Data::collect(...)` result from the controller
- Internal actions and services: keep them package-agnostic unless there is a clear reason not to

Prefer this flow:

`route -> controller -> action/service -> Data response`

Avoid this flow:

`route -> FormRequest + Data + manual response array`

## Request Data

For JSON write endpoints, the request DTO owns the boundary.

Use:

- Constructor-promoted typed properties
- Inferred validation rules from PHP types
- Validation attributes for field-level rules
- Manual `rules()` only for cases that are genuinely awkward or cross-field heavy

Example:

```php
<?php

namespace App\Data\Api\Auth;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class LoginData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public string $email,
        #[Required]
        public string $password,
    ) {}
}
```

Preferred controller pattern:

```php
public function __invoke(LoginData $data): LoginResponseData
{
    $token = $this->loginUser->handle($data->email, $data->password);

    return LoginResponseData::fromToken($token);
}
```

### Validation Boundaries

Validation does not automatically happen for every `Data::from(...)` call in this repo.

- Automatic validation happens for request injection and `Data::from($request)`
- Automatic validation does not happen for arbitrary arrays because the repo uses `OnlyRequests`
- Outside request injection, use `validateAndCreate()` or pass already-validated data

Do not assume this validates:

```php
UserFilterData::from($payload);
```

Use this when validation is required outside the request lifecycle:

```php
UserFilterData::validateAndCreate($payload);
```

## Query and Filter Data

For JSON read endpoints with meaningful query contracts, prefer `Data` objects for filters, sorting, includes, and pagination inputs instead of introducing a parallel `FormRequest` pattern.

Good fit:

- `page`
- `per_page`
- `sort`
- `include`
- filter objects that shape an index response

Example:

```php
final class UserIndexData extends Data
{
    public function __construct(
        #[Min(1)]
        public int $page = 1,
        #[Min(1), Max(100)]
        public int $per_page = 15,
        public ?string $include = null,
    ) {}
}
```

Use a separate query DTO when the endpoint has a real query contract. If the query needs become unusually complex or the DTO becomes harder to understand than a focused request class, choose the clearer option deliberately.

## Response Data

Every API endpoint should return an explicit response contract.

Do:

- Return `Data` objects directly from controllers
- Map fields intentionally with `fromModel()` or another explicit factory
- Use response-only `Resource` classes only when you truly want transformation without validation behavior

Do not:

- Return raw Eloquent models from API routes
- Hand-build `response()->json([...])` arrays for standard resource payloads
- Rely on implicit model normalization when the API shape matters

Example:

```php
<?php

namespace App\Data\Api;

use App\Models\User;
use DateTimeInterface;
use Spatie\LaravelData\Data;

final class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?DateTimeInterface $email_verified_at,
        public ?DateTimeInterface $created_at,
        public ?DateTimeInterface $updated_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->getKey(),
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at,
            created_at: $user->created_at,
            updated_at: $user->updated_at,
        );
    }
}
```

## Current Repo Example

The canonical exemplar in this repo is `/api/v1/user`.

- Route name: `api.v1.user.show`
- Controller: `App\Http\Controllers\Api\V1\CurrentUserController`
- Response DTO: `App\Data\Api\UserData`

This endpoint shows the preferred read pattern for simple authenticated resources:

- no raw model response
- no manual JSON array
- explicit `Data` mapping
- thin invokable controller

Use this as the first pattern to copy for future API endpoints.

### Legacy Endpoint Note

`POST /api/login` is currently left untouched on purpose.

- It still uses `FormRequest` plus a manual JSON response
- Do not treat it as the preferred pattern for new API endpoints
- When building new endpoints, follow `/api/v1/user`, not `/api/login`

## Collections and Nesting

When a property contains nested data, type it explicitly.

Single nested object:

```php
public OrganizationData $organization,
```

Nested collections must declare their item type. Prefer PHPDoc or typed collection patterns that static analysis understands.

Example:

```php
/** @var array<PostData> */
public array $posts,
```

or:

```php
#[DataCollectionOf(PostData::class)]
public array $posts,
```

Rules:

- Never expose raw nested model arrays in a response DTO
- Never leave nested lists untyped
- Always map nested DTOs intentionally when the source relation shape is not already suitable

## Collections and Pagination Responses

Use `collect()` for list endpoints.

Simple collection:

```php
return UserData::collect(User::query()->latest()->get());
```

Paginated collection:

```php
return UserData::collect(
    User::query()->latest('id')->paginate(15)
);
```

Rules:

- Pass the paginator directly to `collect()`
- Do not call `items()` before returning
- Do not manually rebuild pagination meta
- Let the package preserve `data`, `links`, and `meta` for paginated responses

Use the standard paginator types unless you specifically need `DataCollection`, `PaginatedDataCollection`, or `CursorPaginatedCollection` features like include/exclude chaining.

## Validation Attributes

Prefer validation attributes for field-level rules close to the property they govern.

Good fits:

- `#[Email]`
- `#[Max(255)]`
- `#[Min(1)]`
- `#[Unique(...)]`
- `#[Exists(...)]`
- conditional validation attributes when they remain readable

Use manual `rules()` when:

- validation is heavily dynamic
- the attribute form becomes harder to understand than a rules array
- the rule needs complex payload inspection

Use `authorize()` on the data class only when request-level authorization truly belongs there. Otherwise keep authorization in policies or controller/action flow per the existing app patterns.

## Casts

Casts convert simple input into richer types.

Use local casts for endpoint-specific behavior:

```php
#[WithCast(MoneyCast::class)]
public Money $price,
```

Use global casts only when the behavior is truly cross-cutting across the app.

This repo already has global casts for:

- `DateTimeInterface`
- backed enums

Guidelines:

- Controllers should not parse domain value objects manually
- If a property is a non-primitive domain type, give it a cast
- If an iterable contains non-primitive items, be explicit about item typing and config assumptions

Do not enable advanced iterable casting globally just because it exists. In this repo, `'cast_and_transform_iterables'` remains `false` unless a proven need appears.

## Transformers

Transformers define output formatting.

Use transformers when the output must differ from the in-memory PHP representation.

- Dates already use the repo-wide global transformer and `DATE_ATOM`
- Use local transformers for API-specific output formatting
- Keep output formatting in DTOs, not controllers

If a custom type needs both input casting and output transformation, prefer a combined cast/transformer class with `WithCastAndTransformer`.

## Mapping and Naming

Keep API output snake_case unless there is a real product requirement to do otherwise.

Use explicit mapping when:

- the external API name differs from the PHP property name
- the request payload uses a different input key
- the output should remain stable even if model attributes change

Do not turn on broad global name mapping just to save a few explicit mappings.

## Lazy Properties and Includes

Lazy properties are useful, but they are not the default for ordinary fields.

Use lazy properties only when:

- a relationship or subtree is expensive
- it is optional for the client
- the include name is documented and predictable
- the underlying query/load strategy is explicit

Good fit:

- optional nested analytics block
- expensive relationship graph
- conditionally included audit trail

Bad fit:

- core fields like `id`, `name`, `email`
- small relations that should always be present
- using lazy properties as a substitute for deciding the real contract

If you use request-driven includes, explicitly whitelist them with `allowedRequestIncludes()`.

## Model-to-Data Mapping

Prefer explicit `fromModel()` methods when returning API payloads.

Why:

- exposed fields stay intentional
- framework/model internals do not leak into the API
- nested loading decisions stay visible
- refactors are safer

Implicit `::from($model)` is acceptable only when the model shape and API shape are intentionally identical and still easy to reason about.

## Eloquent Data Casting

`laravel-data` can also be used as an Eloquent cast for JSON columns and collections.

Use that when:

- a model stores structured JSON with domain meaning
- a persisted value object or collection belongs on the model itself

Do not introduce Eloquent data casts just because an endpoint uses a DTO. API DTOs and persisted model casts solve different problems.

## Performance

This repo already enables structure caching for `app/Data`.

After creating or changing many data classes, especially for deployment workflows, cache structures:

```bash
php artisan data:cache-structures
```

Notes:

- tests automatically disable this cache
- do not optimize prematurely for tiny DTO counts
- do not disable caching without a concrete issue

## Recommended Endpoint Pattern

### Read Endpoint

```php
public function __invoke(Request $request): UserData
{
    /** @var User $user */
    $user = $request->user();

    return UserData::fromModel($user);
}
```

### Write Endpoint

```php
public function __invoke(CreatePostData $data): PostData
{
    $post = $this->createPost->handle(
        title: $data->title,
        body: $data->body,
    );

    return PostData::fromModel($post);
}
```

## Red Flags

- Using both `FormRequest` and `Data` as the new endpoint boundary
- Returning raw models from API routes
- Building standard resource payloads with `response()->json([...])`
- Calling `Data::from($payload)` and assuming validation happened
- Leaving nested arrays or collections untyped
- Manually flattening paginators and losing `meta` or `links`
- Formatting dates, enums, or value objects inside controllers
- Using lazy properties for routine fields
- Reaching for advanced features before a simple DTO would do

## Common Mistakes

Mistake: keep a `FormRequest` and then create `Data` from it
Fix: use injected request `Data` as the primary boundary for new JSON endpoints

Mistake: return `$request->user()` directly
Fix: return `UserData::fromModel($user)`

Mistake: use untyped `array` for nested DTO lists
Fix: declare the item DTO type explicitly

Mistake: call `->items()` on paginators before `collect()`
Fix: pass the paginator itself to `collect()`

Mistake: validate in a DTO, then revalidate or reshape again in the controller
Fix: keep validation and shaping inside the DTO boundary and let the controller orchestrate only

## Testing Expectations

Every new endpoint pattern should be covered by Pest feature tests.

For API DTO endpoints, usually assert:

- authentication behavior
- validation behavior
- public JSON contract
- absence of sensitive fields
- pagination shape when relevant

Prefer assertions on the response contract, not on internal serialization accidents.

## Quick Checklist

- Is this a JSON API endpoint?
- Is request validation in a `Data` object?
- Is the response an explicit `Data` contract?
- Are nested collections typed?
- Are custom types cast or transformed in the DTO rather than the controller?
- Is pagination returned through `collect($paginator)`?
- Are advanced features only used because they solve a real problem?
