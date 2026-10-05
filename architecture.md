
# Holvek Laravel Architecture

## 1. Architectural Intent

Holvek Laravel is a Laravel-native integration package.

It is not a second Laravel application embedded inside the host application.

Its responsibility is to expose Holvek functionality through APIs that feel natural to Laravel developers while keeping the host application's:

* performance;
* configuration;
* routing;
* authentication;
* database;
* frontend;
* deployment

under the application's control.

The package must remain an integration layer, not become an independent monolith.

---

# 2. Architectural Principles

The architecture follows six principles:

1. **Laravel-native**
2. **Explicit boundaries**
3. **Lazy work**
4. **Minimal dependencies**
5. **Stable public contracts**
6. **Host-application safety**

---

# 3. High-Level Architecture

```text
                    Host Laravel Application
                              │
                              ▼
                     ┌─────────────────┐
                     │  Holvek Laravel │
                     │     Package     │
                     └────────┬────────┘
                              │
             ┌────────────────┼────────────────┐
             │                │                │
             ▼                ▼                ▼
       Public API        Application       Laravel
       / Facade           Services        Integration
             │                │                │
             └────────┬───────┴────────┬───────┘
                      │                │
                      ▼                ▼
                 API Client          Cache
                      │
                      ▼
                 Holvek API
```

The package should not allow arbitrary application code to bypass the defined Holvek boundary.

---

# 4. Layers

## 4.1 Public API

The public API is what application developers interact with.

Examples:

```php
Holvek::visibility()
Holvek::indexing()
Holvek::citations()
```

or dependency-injected contracts/services.

The public API must remain small.

---

# 5. Application Services

Application services coordinate actual use cases.

Examples:

```text
VisibilityService
IndexingService
CitationService
SyncService
```

These classes should answer:

> "What operation does the developer want to perform?"

They should not know unnecessary details about HTTP transport.

---

# 6. Infrastructure

Infrastructure handles external concerns.

Examples:

```text
ApiClient
HttpTransport
ResponseMapper
CacheAdapter
```

Infrastructure answers:

> "How does the package communicate with the external system?"

---

# 7. Laravel Integration

Laravel-specific integration includes:

```text
ServiceProvider
Facade
Commands
Routes
Middleware
Config
Published resources
```

Laravel integration must not leak deeply into the core application logic unless Laravel functionality is itself the purpose of that component.

---

# 8. Dependency Direction

Dependencies should flow inward.

Preferred:

```text
Laravel Integration
       ↓
Application Services
       ↓
Contracts
       ↓
Infrastructure
```

Avoid:

```text
Model → Controller → Service → Model → Facade → API
```

Avoid circular dependencies.

---

# 9. API Client Boundary

The external Holvek API is an infrastructure boundary.

A typical flow:

```text
Facade
  ↓
Public Service
  ↓
Contract
  ↓
API Client
  ↓
Laravel HTTP Client
  ↓
Holvek API
```

The service should not construct raw HTTP requests throughout the codebase.

---

# 10. Response Mapping

External API responses should not automatically become package-wide associative arrays.

Where response semantics matter, map them into stable package-level data structures.

For example:

```php
final readonly class VisibilityResult
{
    public function __construct(
        public string $status,
        public ?int $score,
        public array $signals,
    ) {}
}
```

The purpose is to prevent the entire package from becoming coupled to the exact JSON structure of the remote API.

Do not create a DTO for every response automatically.

Use one when it creates meaningful stability or readability.

---

# 11. Error Boundary

External errors must be normalized at the infrastructure boundary.

Example:

```text
HTTP 401
    ↓
ApiClient
    ↓
AuthenticationException
    ↓
Application
```

The application should not need to understand the internals of the HTTP library.

---

# 12. Configuration Architecture

Configuration should flow:

```text
config/holvek.php
       ↓
Service Provider
       ↓
Configured Services
```

Runtime services should not repeatedly read environment variables.

Configuration should be resolved once through Laravel's configuration system.

---

# 13. Authentication

Authentication credentials belong to configuration/secrets.

Never expose them to:

* Blade;
* browser JavaScript;
* public responses;
* logs;
* exceptions;
* serialized models.

---

# 14. Persistence

Persistence is optional.

The package should not introduce a database unless a feature genuinely requires persistent state.

Prefer external API state when the Holvek platform is the system of record.

Use local persistence for things such as:

* synchronization state;
* local metadata;
* durable configuration;
* webhook state;
* cache/index state

only where there is a clear benefit.

---

# 15. Local State vs Remote State

The architecture must explicitly distinguish:

### Remote state

Owned by Holvek API.

Examples:

* visibility measurements;
* citation data;
* discovery intelligence;
* remote account information.

### Local state

Owned by the Laravel application/package.

Examples:

* connection configuration;
* synchronization cursor;
* local cache;
* installation metadata.

Never create two authoritative copies of the same data without a synchronization strategy.

---

# 16. Caching Architecture

Caching is an optimization, not a source of truth.

```text
Application
    ↓
Service
    ↓
Cache
    ↓ miss
API
```

Never make correctness dependent on a cache entry.

Cache keys must be:

* deterministic;
* namespaced;
* collision-resistant.

Example:

```text
holvek:{tenant}:{resource}:{identifier}:{version}
```

---

# 17. Multi-Tenancy

The package must not invent tenancy semantics.

If the host application is multi-tenant, Holvek should integrate with the host application's tenant resolution strategy.

Do not assume:

```php
Auth::user()->tenant_id
```

or any specific tenancy package.

Instead, use explicit configuration/contracts where tenant context is required.

---

# 18. Authentication Independence

The package must not assume a specific authentication guard.

Do not require:

```php
web
```

unless the feature specifically needs browser authentication.

Package APIs should work in:

* web applications;
* API applications;
* CLI;
* queues;
* scheduled tasks.

---

# 19. Queue Architecture

Queue support must remain optional.

The package should not require Redis, Horizon, or another queue backend merely to install it.

If asynchronous work is supported:

```text
Application
   ↓
Dispatch Job
   ↓
Queue
   ↓
Holvek Service
   ↓
API
```

Jobs should contain small, serializable payloads.

---

# 20. Event Architecture

Events should represent meaningful domain occurrences.

Examples:

```text
HolvekConnected
VisibilityUpdated
SynchronizationCompleted
SynchronizationFailed
```

Do not emit events for every internal method.

Events are extension points, not decoration.

---

# 21. Route Architecture

Routes are optional.

If the package exposes routes:

```text
/holvek/...
```

should be configurable.

Route registration must not conflict with application routes.

Webhook routes should be:

* isolated;
* authenticated/signed where applicable;
* rate-limited where appropriate;
* excluded from CSRF protection only when technically justified.

---

# 22. Command Architecture

Artisan commands should be thin entry points.

Example:

```text
holvek:sync
       ↓
SyncService
```

Do not implement the synchronization engine directly inside the command.

Commands should provide:

* argument parsing;
* option parsing;
* console feedback;
* invocation of application services.

---

# 23. Package Installation

Installation must be as frictionless as possible.

The package should support Laravel package discovery through Composer metadata where appropriate.

Do not require manual provider registration if Laravel package discovery can safely handle it.

Do not execute destructive operations during installation.

Do not automatically:

* migrate databases;
* make network calls;
* create accounts;
* alter application routes;
* publish files without explicit user action.

Laravel package discovery and service providers are the normal integration mechanisms for packages.

---

# 24. Directory Responsibility

## `src/`

Production package code.

## `src/Contracts/`

Only meaningful extension boundaries.

## `src/Data/`

Stable data structures crossing meaningful boundaries.

## `src/Exceptions/`

Package-level exceptions.

## `src/Http/`

HTTP/API infrastructure.

## `src/Services/`

Application-level operations.

## `src/Providers/`

Laravel service providers.

## `src/Support/`

Small reusable infrastructure that does not justify another architectural layer.

## `config/`

Package configuration.

## `routes/`

Only package routes.

## `resources/`

Views, translations and assets.

## `tests/`

All automated tests.

---

# 25. Architectural Anti-Patterns

Never introduce:

### God Service

```text
HolvekManager
```

handling everything.

### Repository-for-Everything

Do not create repositories simply because repositories exist in enterprise tutorials.

### Service-for-Every-Method

Do not create:

```text
GetUserService
CreateUserService
UpdateUserService
DeleteUserService
```

unless those operations genuinely require independent application boundaries.

### Controller Business Logic

Never move business logic into controllers because it is convenient.

### Model-as-API-Client

Never make an Eloquent model responsible for external HTTP communication.

### Hidden Network Calls

Never perform API calls from accessors, serialization, constructors, or views.

### Global State

Do not maintain mutable static state across requests.

---

# 26. Architecture Evolution

Architecture should evolve from demonstrated requirements.

Do not build:

* extension systems;
* event buses;
* strategy factories;
* provider registries;
* repositories;
* elaborate dependency graphs

until actual requirements justify them.

The architecture should become more capable without becoming more complicated.

---

# 27. Architectural Review Questions

Before approving a change ask:

1. What responsibility was added?
2. Where does that responsibility belong?
3. Does an existing class already own it?
4. Does this introduce a new public API?
5. Does this introduce persistence?
6. Does this introduce network I/O?
7. Does this affect every request?
8. Can it be lazy?
9. Can it be removed without affecting unrelated features?
10. Would a Laravel maintainer recognize the structure immediately?

If the answer to the last question is no, reconsider the design.