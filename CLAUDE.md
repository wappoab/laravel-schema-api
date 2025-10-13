# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`laravel-schema-api` is a Laravel package that automatically exposes Eloquent models through HTTP API endpoints. It provides RESTful access to models with automatic schema detection, streaming JSON responses, and support for incremental sync operations.

## Development Commands

### Testing
```bash
composer test                    # Run all tests with Pest
composer test-coverage          # Run tests with coverage report
vendor/bin/pest --filter=SyncModelTest  # Run specific test file
```

### Code Quality
```bash
composer format                  # Format code with Laravel Pint
composer analyse                 # Run PHPStan static analysis
```

### Package Development
```bash
composer run prepare            # Discover packages (runs automatically after autoload-dump)
```

### Client Resource Generation
```bash
php artisan app:generate-client-resources  # Generate TypeScript types and Vue forms from models
```

## Architecture

### Resolver Pattern (Chain of Responsibility)

The package uses a configurable resolver pattern with decorator chains for two key responsibilities:

1. **ModelResolver**: Maps table names to Eloquent model classes
   - Interface: `ModelResolverInterface`
   - Implementations: `NamespaceModelResolver`, `MorphMapModelResolver`
   - Decorators: `ValidatingModelResolver` → `CachingModelResolver` (applied in order)
   - Configured in `config/schema-api.php` under `model_resolver`

2. **ResourceResolver**: Maps model classes to JSON Resource classes
   - Interface: `ResourceResolverInterface`
   - Implementations: `NamespaceResourceResolver`, `UseSchemaApiJsonResourceAttributeResolver`, `GuessResourceNameResolver`
   - Decorators: `ValidatingResourceResolver` → `CachingResourceResolver`
   - Configured in `config/schema-api.php` under `resource_resolver`

The decorator pattern allows adding validation and caching layers around any resolver implementation without modifying the resolver itself.

### HTTP Controllers

Three main controllers handle all API operations:

- **SchemaApiIndexController** (`GET /schema-api/{table?}`)
  - Lists all models or specific model collection
  - Streams NDJSON (newline-delimited JSON) for memory efficiency
  - Supports `?since` parameter for incremental sync (with soft deletes)
  - Applies query modifiers via `#[ApplyQueryModifier]` attributes
  - Optional gzip compression via `?gzip` parameter

- **SchemaApiGetController** (`GET /schema-api/{table}/{id}`)
  - Retrieves single model by ID
  - Resolves model and resource classes dynamically

- **SchemaApiSyncController** (`PUT /schema-api/sync`)
  - Batch operations for create/update/delete
  - Uses `ModelOperationCollection` to track changes during request
  - Validates via `ValidationRulesResolver` and model attributes

### Query Modifiers

Models can use the `#[ApplyQueryModifier]` attribute to customize queries:

```php
#[ApplyQueryModifier(SortQueryModifier::class, ['column' => 'name'])]
#[ApplyQueryModifier(FilterQueryModifier::class)]
class MyModel extends Model {}
```

Built-in modifiers:
- `FilterQueryModifier` - Apply filters from query parameters
- `SortQueryModifier` - Sort by specific column
- `LatestFirstModifier` - Order by created_at DESC
- `UpdatedFirstModifier` - Order by updated_at DESC

All modifiers implement `QueryModifierInterface` and modify the Eloquent `Builder` instance.

### Validation Rules

Validation rules are resolved in this order:

1. Model's `#[UseValidationRulesProvider]` attribute pointing to a `ValidationRulesProviderInterface` implementation
2. Automatic generation from database schema via `SchemaValidationRulesGenerator` and `ColumnRuleMapper`

The `ColumnRuleMapper` maps database column types to Laravel validation rules (e.g., `varchar` → `string`, `integer` → `integer`, etc.).

### Model Attributes

- `#[ApiIgnore]` - Exclude model from API entirely
- `#[ApplyQueryModifier]` - Apply query modifiers (repeatable)
- `#[UseValidationRulesProvider]` - Custom validation rules provider
- `#[UseSchemaApiJsonResource]` - Specify custom JSON resource class

### Testing Infrastructure

- Tests extend `TestCase` which extends Orchestra Testbench
- Uses `RefreshDatabase` trait for clean database state
- Test models/migrations in `tests/Fakes` and `tests/Migrations`
- Custom `streamedJson()` macro on `TestResponse` parses NDJSON responses
- Tests organized as:
  - `tests/Tests/Unit/` - Unit tests
  - `tests/Tests/Feature/` - Feature/integration tests

### Configuration

Key config values in `config/schema-api.php`:

- `date_format` - ISO 8601 date format for JSON responses (used by `HasDateFormat` trait)
- `http.base_path` - API route prefix (default: `/schema-api`)
- `http.middleware` - Middleware group (default: `api`)
- `http.gzip_level` - Response compression level (0-9)
- `model_resolver.driver` - Which resolver implementation to use
- `resource_resolver.driver` - Which resource resolver to use

### Service Provider Registration

The `SchemaApiServiceProvider` registers:
- Singleton bindings for ModelResolver, ResourceResolver, ValidationRulesResolver
- Scoped binding for `ModelOperationCollection` (per-request change tracking)
- API routes from `routes/api.php`
- Artisan command `GenerateClientResources`

## Key Design Patterns

1. **Streaming Responses**: Uses `response()->stream()` with NDJSON to handle large datasets without memory issues
2. **Decorator Pattern**: Resolvers are wrapped in validation/caching decorators
3. **Attribute-Based Configuration**: Uses PHP 8 attributes for model-level configuration
4. **Operation Tracking**: `ModelOperationCollection` tracks CRUD operations during sync requests
5. **Type Mapping**: `TableToTypeMapper` and `TypeToTableMapper` convert between table names and type aliases
