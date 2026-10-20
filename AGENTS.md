# Quinrel Laravel — Agent Engineering Constitution

## 1. Mission

You are working on a production Laravel package.

The objective is not merely to make code that works.

The objective is to produce code that:

* feels native to modern Laravel;
* is understandable to an experienced Laravel developer;
* is small enough to maintain;
* has explicit boundaries;
* has predictable runtime behavior;
* has minimal framework coupling outside intentional integration points;
* has excellent test coverage;
* introduces no unnecessary abstractions;
* introduces no unnecessary dependencies;
* does not degrade the host application's performance;
* remains compatible with supported Laravel and PHP versions;
* behaves correctly in both development and production;
* remains safe when installed into an existing application;
* can be maintained by humans after AI-assisted development ends.

**Do not optimize for generating more code. Optimize for solving the smallest correct problem.**

---

# 2. Non-Negotiable Rules

These rules override convenience.

## 2.1 Do not invent architecture

Before adding a class, directory, interface, service, trait, event, listener, repository, DTO, or dependency:

1. Search the existing codebase.
2. Identify an existing pattern that already solves the problem.
3. Reuse it when appropriate.
4. Introduce a new abstraction only when it creates a meaningful boundary.

Do not create architecture merely because a pattern exists in another project.

---

## 2.2 Laravel first

Prefer Laravel's native mechanisms before introducing custom infrastructure.

Prefer:

* service container;
* service providers;
* configuration;
* HTTP client;
* cache;
* queues;
* events;
* notifications;
* authorization;
* validation;
* Eloquent;
* migrations;
* Artisan commands;
* Laravel's testing facilities;
* Laravel contracts;
* Laravel's logging facilities.

Do not recreate Laravel functionality.

Bad:

```php
class CustomContainer
{
    // Reinventing Laravel's container.
}
```

Good:

```php
$this->app->singleton(QuinrelClient::class);
```

---

# 3. Repository Understanding Protocol

Before modifying code, inspect:

1. `composer.json`
2. `AGENTS.md`
3. `architecture.md`
4. `development.md`
5. `testing.md`
6. `performance.md`
7. `security.md`
8. relevant source files
9. relevant tests
10. existing configuration
11. existing service provider
12. package scripts

Do not begin implementation from the user's request alone.

Understand the repository first.

---

# 4. Change Classification

Every requested change must first be classified.

### Type A — Bug fix

Change the smallest amount of code necessary.

### Type B — Existing feature modification

Preserve existing contracts unless the request explicitly requires a breaking change.

### Type C — New feature

Determine:

* public API;
* configuration impact;
* persistence impact;
* HTTP impact;
* performance impact;
* security implications;
* testing requirements;
* backwards compatibility.

### Type D — Refactor

A refactor must preserve observable behavior unless explicitly stated otherwise.

Do not mix unrelated refactoring into a feature.

---

# 5. Public API Discipline

Anything that package consumers can reasonably depend upon is a public contract.

Treat the following as public unless explicitly marked internal:

* exported classes;
* contracts/interfaces;
* facade methods;
* configuration keys;
* Artisan commands;
* route names;
* route parameters;
* published config;
* published migrations;
* published assets;
* events;
* event payloads;
* exceptions;
* documented methods;
* documented environment variables.

Do not casually rename or remove them.

If a breaking change is necessary:

1. document it;
2. update tests;
3. update changelog;
4. update documentation;
5. determine whether a major version is required.

---

# 6. Internal Code

Internal implementation details should remain internal.

Do not expose classes simply because another class currently uses them.

Do not turn every class into an interface.

Do not create public APIs for hypothetical future consumers.

---

# 7. Dependency Rules

Before adding a Composer dependency ask:

1. Can Laravel already do this?
2. Can PHP already do this?
3. Is the dependency solving a substantial problem?
4. Does it materially reduce maintenance?
5. Does it introduce runtime overhead?
6. Does it introduce security or compatibility risk?
7. Is it required in production or only development?

If the answer is unclear, do not add it.

### Production dependencies must be justified.

Avoid dependency accumulation.

One high-quality dependency is preferable to five small packages that duplicate framework functionality.

---

# 8. Service Provider Rules

The service provider is the integration boundary between Quinrel and Laravel.

Keep `register()` focused on:

* bindings;
* configuration;
* service registration;
* dependency wiring.

Keep `boot()` focused on:

* publishing;
* routes;
* views;
* translations;
* migrations;
* commands;
* other boot-time integration.

Do not place business logic inside the service provider.

Do not perform network requests during service provider registration or boot.

Do not query the database during package boot.

Do not eagerly initialize expensive services.

Laravel service providers are specifically intended as the framework integration point.

---

# 9. Configuration

Configuration must be:

* explicit;
* documented;
* safe by default;
* environment-aware;
* immutable during normal request execution.

Never access environment variables directly throughout application code.

Bad:

```php
env('QUINREL_API_KEY')
```

inside services.

Good:

```php
config('quinrel.api.key')
```

Use `env()` only in configuration files.

---

# 10. HTTP/API Rules

All Quinrel platform communication must pass through a clearly defined HTTP boundary.

Do not scatter HTTP requests throughout:

* controllers;
* models;
* commands;
* views;
* helpers.

Prefer:

```text
Application
    ↓
Quinrel Service / Contract
    ↓
API Client
    ↓
Laravel HTTP Client
    ↓
Quinrel API
```

The API client must handle:

* authentication;
* base URL;
* timeouts;
* retries where appropriate;
* response normalization;
* error translation;
* request IDs/correlation IDs;
* transport failures.

Do not hide network calls inside model accessors or casts.

---

# 11. Never Perform Network I/O Accidentally

The following must never trigger an unexpected HTTP request:

* model property access;
* model serialization;
* `toArray()`;
* Blade rendering;
* configuration loading;
* service provider boot;
* validation;
* authorization checks;
* logging;
* debugging;
* PHPUnit assertions.

Network operations must be explicit.

Good:

```php
$visibility = $quinrel->visibility()->for($site);
```

Bad:

```php
$site->quinrel_visibility
```

if that property silently performs a network request.

---

# 12. Database Rules

Do not assume the host application wants Quinrel database tables.

If persistence is required:

* use package migrations;
* use unique, clearly namespaced table names;
* document the tables;
* make migrations safe;
* never modify application-owned tables without explicit configuration;
* never silently migrate existing application data.

Do not introduce Eloquent models when a simple API/cache/service representation is sufficient.

Do not use the database merely because it is available.

---

# 13. Eloquent Rules

When Eloquent is appropriate:

* relationships must be explicit;
* scopes should express reusable query logic;
* casts should remain lightweight;
* accessors must not perform I/O;
* model events must have a clear reason;
* avoid hidden queries;
* avoid N+1 relationships;
* avoid unnecessary global scopes.

Do not use Eloquent as a general-purpose service layer.

---

# 14. Controller Rules

Controllers should be thin.

A controller may:

* receive a request;
* authorize;
* validate;
* invoke an application service/action;
* return a response.

A controller should not contain:

* complex business logic;
* API orchestration;
* large database queries;
* repeated transformation logic;
* extensive conditional workflows.

Bad:

```php
public function store(Request $request)
{
    // 100+ lines of application logic.
}
```

Prefer:

```php
public function store(StoreSomethingRequest $request)
{
    $result = $this->createSomething->handle(
        $request->validated()
    );

    return response()->json($result);
}
```

Do not create an action class for trivial one-line operations merely to satisfy a pattern.

---

# 15. Service Classes

A service should have a coherent responsibility.

Good:

```text
VisibilityService
IndexingService
CitationService
ConfigurationService
```

Bad:

```text
QuinrelManager
QuinrelHelper
QuinrelUtility
QuinrelService
```

A class called `Manager` or `Helper` often indicates unclear responsibility.

---

# 16. Contracts

Use interfaces when they create an actual substitution boundary.

Good candidates:

* API transport;
* external provider;
* storage adapter;
* cache adapter;
* discovery provider;
* analytics provider.

Do not create:

```php
interface UserServiceInterface {}
```

only because "all services should have interfaces."

---

# 17. DTO / Data Object Rules

Use data objects when they provide meaningful structure at a boundary.

Good:

```php
final readonly class VisibilityResult
{
    public function __construct(
        public string $status,
        public ?int $score,
    ) {}
}
```

Do not create DTOs for trivial internal method calls where arrays are clearer.

Do not build a DTO hierarchy.

Prefer immutable data objects for external API responses where practical.

---

# 18. Exceptions

Exceptions must communicate meaningful failure states.

Prefer specific exceptions:

```text
AuthenticationException
ApiException
RateLimitException
ConfigurationException
TransportException
```

Avoid:

```text
QuinrelException
GeneralException
SomethingWentWrongException
```

unless the exception genuinely represents the package-wide boundary.

Never expose raw third-party exceptions as the package's primary public API unless intentional.

---

# 19. Error Handling

Do not swallow exceptions.

Bad:

```php
try {
    // ...
} catch (Throwable $e) {
    return null;
}
```

If an error is intentionally recoverable:

* document why;
* log appropriately;
* return an explicit fallback;
* preserve observability.

Never convert every failure into `null`.

---

# 20. Logging

Log useful operational information, not implementation noise.

Never log:

* API keys;
* tokens;
* passwords;
* cookies;
* authorization headers;
* personally identifiable information unless explicitly required;
* full request bodies containing sensitive information.

Prefer structured context:

```php
Log::warning('Quinrel API request failed.', [
    'operation' => $operation,
    'status' => $response->status(),
    'request_id' => $requestId,
]);
```

---

# 21. Performance Constitution

Performance is a feature.

Every new feature must consider:

* request count;
* database queries;
* memory;
* serialization;
* cache behavior;
* network latency;
* startup cost;
* queue usage;
* asset size.

Avoid:

* unnecessary API requests;
* unnecessary database queries;
* repeated configuration parsing;
* repeated serialization;
* expensive constructors;
* eager loading everything;
* recursive transformations;
* unnecessary collections;
* unnecessary closures in hot paths;
* polling from normal web requests.

Do not optimize prematurely.

However, do not introduce obviously expensive behavior and plan to optimize it later.

---

# 22. Caching

Cache only when there is a defined invalidation or expiration strategy.

Every cache must answer:

* what is cached?
* why?
* for how long?
* what invalidates it?
* what happens when the cache is unavailable?

Never cache secrets or sensitive data without an explicit security model.

Use Laravel's cache abstraction rather than directly coupling to Redis.

---

# 23. Queue Rules

Use queues for work that is:

* slow;
* externally dependent;
* retryable;
* non-critical to the current HTTP response.

Do not automatically queue everything.

The user experience and consistency requirements determine whether work should be synchronous.

Every queued job must define:

* idempotency expectations;
* retry behavior;
* failure handling;
* timeout expectations;
* payload size.

Never put huge Eloquent models or unnecessary data into jobs.

---

# 24. API Rate Limits

Assume external APIs can:

* throttle;
* timeout;
* return malformed responses;
* temporarily fail;
* change latency;
* become unavailable.

Use bounded retries with backoff only where retrying is safe.

Never implement infinite retries.

Do not retry deterministic client errors.

---

# 25. Security

Treat all external input as untrusted.

Validate:

* HTTP input;
* configuration values;
* API responses;
* webhook payloads;
* file paths;
* URLs;
* identifiers.

Never trust external API responses merely because they came from a known provider.

Use:

* authorization;
* validation;
* signed webhooks where applicable;
* secure secrets;
* least privilege.

Never expose API credentials to frontend JavaScript.

---

# 26. Routes

Package routes must be:

* intentionally namespaced;
* configurable where appropriate;
* middleware-aware;
* documented;
* tested.

Do not register routes merely because they might be useful.

Avoid route collisions with the host application.

Do not hardcode assumptions about the host application's authentication system.

---

# 27. Frontend / Assets

If the package contains UI:

* keep assets isolated;
* do not overwrite host styles;
* avoid globally polluting CSS;
* avoid shipping a full frontend framework unnecessarily;
* minimize JavaScript;
* lazy-load expensive functionality.

A package should not force a host application to adopt a frontend architecture merely to install the package.

---

# 28. Blade Rules

Blade templates must remain presentation-focused.

Do not place:

* database queries;
* HTTP calls;
* complex business logic;
* API orchestration

inside Blade.

Use view models, data objects, or prepared data when necessary.

---

# 29. Naming

Prefer Laravel conventions.

Classes:

```text
PascalCase
```

Methods and variables:

```text
camelCase
```

Database:

```text
snake_case
```

Booleans should read naturally:

```php
$isEnabled
$hasAccess
$shouldRefresh
```

Avoid vague names:

```php
$data
$result
$temp
$thing
$info
```

when a precise name is possible.

---

# 30. Type Safety

Use strict typing.

PHP files should normally begin with:

```php
<?php

declare(strict_types=1);
```

Prefer:

* typed properties;
* typed parameters;
* return types;
* readonly properties where appropriate;
* enums where a finite domain is genuinely represented.

Avoid `mixed` unless unavoidable.

Avoid excessive nullable types.

Do not use PHPDoc to compensate for weak type design.

---

# 31. Modern PHP

Use modern PHP features where they improve clarity.

Potentially appropriate:

* readonly classes;
* enums;
* constructor property promotion;
* named constructors;
* match expressions;
* attributes where Laravel supports them;
* union/intersection types;
* first-class callables.

Do not use new language features merely because they exist.

Compatibility requirements always win.

---

# 32. Collections

Use Laravel Collections when collection semantics improve readability.

Do not convert every array into a Collection.

Avoid long chains that hide complex business logic.

If a collection chain becomes difficult to understand, extract a named operation.

---

# 33. Static Helpers

Avoid static utility classes.

Static methods are acceptable for:

* pure value construction;
* deterministic parsing;
* named constructors;
* framework-required APIs.

They are not a substitute for dependency injection.

---

# 34. Facades

A package facade may provide an excellent public developer experience.

However:

* the underlying service must remain independently injectable;
* business logic must not depend exclusively on the facade;
* tests should be able to use the underlying contract;
* facade methods should remain intentionally small.

---

# 35. Testing

Every behavior change requires tests.

Test at the lowest appropriate level.

Use:

### Unit tests

For:

* deterministic transformations;
* value objects;
* parsers;
* configuration normalization;
* isolated business rules.

### Feature tests

For:

* service provider integration;
* routes;
* configuration;
* database behavior;
* API interactions;
* Artisan commands;
* package installation behavior.

### Contract tests

For:

* external API contracts;
* adapters;
* provider implementations.

Do not write tests merely to increase the test count.

Test behavior.

---

# 36. HTTP Testing

Never make real external HTTP calls in the automated test suite unless a dedicated integration test explicitly requires it.

Use Laravel's HTTP fake facilities.

Tests must verify:

* URL;
* HTTP method;
* headers;
* authentication;
* payload;
* response handling;
* error handling;
* retry behavior where applicable.

---

# 37. Test Quality

Avoid tests that merely restate implementation.

Bad:

```php
expect($service)->toBeInstanceOf(SomeClass::class);
```

when the class type itself is not the behavior being tested.

Prefer:

```php
expect($result->status)->toBe('indexed');
```

Tests should survive reasonable refactoring.

---

# 38. Regression Rule

When fixing a bug:

1. reproduce the bug;
2. add a failing regression test;
3. fix the implementation;
4. confirm the regression test passes;
5. run the relevant test suite.

Never fix a reproducible bug without protecting against recurrence.

---

# 39. Static Analysis

The package should maintain a serious static-analysis baseline.

Prefer:

* PHPStan or Larastan;
* Laravel Pint;
* PHPUnit or Pest;
* Composer validation.

Do not suppress static-analysis errors merely to make CI green.

If suppression is genuinely required, document the reason.

---

# 40. Formatting

Use the repository's configured formatter.

Do not manually invent formatting rules.

Do not reformat unrelated files.

A feature PR should not produce thousands of unrelated formatting changes.

---

# 41. Documentation

Documentation must describe actual behavior.

Do not document planned functionality as implemented functionality.

Examples must compile conceptually and use the real API.

When a public API changes, update:

* README;
* relevant documentation;
* examples;
* changelog;
* tests.

---

# 42. Comments

Code should explain itself.

Comments should explain:

* why something is necessary;
* compatibility constraints;
* non-obvious framework behavior;
* unusual performance decisions;
* security decisions.

Do not write comments that merely translate code into English.

Bad:

```php
// Get the user.
$user = $request->user();
```

Good:

```php
// The package intentionally resolves the authenticated user lazily because
// some host applications register authentication after package boot.
```

---

# 43. AI Slop Prohibition

Never generate code merely to fill space.

Do not introduce:

* generic `Manager` classes;
* generic `Helper` classes;
* unnecessary repositories;
* unnecessary factories;
* unnecessary interfaces;
* duplicate services;
* wrapper classes around one method;
* excessive DTO layers;
* speculative abstractions;
* massive configuration systems;
* unnecessary event chains;
* unnecessary traits;
* deeply nested conditionals;
* clever one-liners that obscure behavior.

The question is not:

> "Can this be abstracted?"

The question is:

> "What problem does this abstraction solve?"

If there is no concrete answer, do not add it.

---

# 44. No Clever Code

Readable code wins.

Avoid:

```php
return collect($items)
    ->filter(...)
    ->map(...)
    ->pipe(...)
    ->tap(...)
    ->when(...)
    ->flatMap(...)
    ->reduce(...);
```

when a straightforward loop is clearer.

Laravel elegance is not synonymous with maximum chaining.

---

# 45. No God Classes

If a class begins handling:

* configuration;
* API calls;
* persistence;
* transformations;
* authorization;
* logging;
* caching;

it probably has multiple responsibilities.

Split it according to actual boundaries.

Do not split merely to achieve a target line count.

---

# 46. No Premature Event Architecture

Do not introduce events/listeners simply because they are decoupled.

Events are appropriate when:

* multiple consumers need the event;
* the event represents a meaningful domain occurrence;
* consumers should remain independent.

For a simple internal method call, call the method.

---

# 47. No Premature Queue Architecture

Do not queue a 20ms deterministic operation.

Do not make a synchronous API call asynchronous unless the product behavior requires it.

---

# 48. No Hidden Side Effects

Methods with names such as:

```text
get()
find()
resolve()
format()
toArray()
```

should not unexpectedly:

* write to the database;
* send HTTP requests;
* dispatch jobs;
* modify global state.

Side effects should be obvious from the API.

---

# 49. Idempotency

Operations that may be retried must be designed for idempotency.

This applies particularly to:

* API synchronization;
* indexing;
* webhooks;
* queued jobs;
* scheduled synchronization;
* cache refreshes.

Repeated execution should not create duplicate records or inconsistent state.

---

# 50. Backwards Compatibility

Do not break consumers casually.

Before changing a public API determine:

* whether the method is public;
* whether it is documented;
* whether it is covered by tests;
* whether configuration depends on it;
* whether a migration is required;
* whether the change belongs in a minor or major release.

---

# 51. Agent Workflow

For every implementation task:

## Step 1 — Understand

Read:

```text
AGENTS.md
architecture.md
development.md
```

Then inspect relevant source and tests.

## Step 2 — Plan

State internally:

* affected components;
* public API impact;
* persistence impact;
* performance impact;
* security impact;
* tests required.

Do not start coding until the boundary is understood.

## Step 3 — Implement

Make the smallest coherent change.

## Step 4 — Test

Run targeted tests first.

Then run the broader suite.

## Step 5 — Inspect

Check:

* formatting;
* static analysis;
* unnecessary abstractions;
* duplicate logic;
* accidental network calls;
* accidental database queries;
* backwards compatibility.

## Step 6 — Document

Update documentation only where behavior or public APIs changed.

## Step 7 — Final Review

Ask:

> Would an experienced Laravel maintainer accept this code without having to explain why the architecture became more complicated?

If not, simplify.

---

# 52. Definition of Done

A task is not complete until:

* implementation works;
* tests pass;
* regression coverage exists where appropriate;
* static analysis passes;
* formatting passes;
* no unnecessary dependency was introduced;
* no public contract was accidentally broken;
* no hidden network/database work was introduced;
* configuration is documented;
* security implications were considered;
* performance implications were considered;
* documentation reflects reality.

---

# 53. Final Principle

The package must feel like it was written by a senior Laravel developer who understands that the package will live inside someone else's application.

It must be:

**small, explicit, composable, testable, performant, secure, Laravel-native and boring in the best possible way.**

When uncertain, prefer the simplest implementation that preserves a clean future boundary.