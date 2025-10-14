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

**Class-level attributes:**
- `#[ApiIgnore]` - Exclude model from API entirely
- `#[ApplyQueryModifier]` - Apply query modifiers (repeatable)
- `#[UseValidationRulesProvider]` - Custom validation rules provider
- `#[UseSchemaApiJsonResource]` - Specify custom JSON resource class

**Method-level attributes:**
- `#[ApiInclude(cascadeDelete: false, forceDelete: false)]` - Apply to relationship methods to:
  - Stream them as root-level entities in index and get endpoints
  - Optionally cascade delete to related items when parent is deleted (if `cascadeDelete: true`)
  - Control hard-delete behavior for models without soft deletes (via `forceDelete`)

Parameters:
- `cascadeDelete` (bool, default: false) - Whether to delete related items when parent is deleted
- `forceDelete` (bool, default: false) - Allow hard-deletion of related models that don't use SoftDeletes

Example:
```php
class Order extends Model
{
    use HasApiIncludeCascadeDelete; // Enable cascade delete functionality

    #[ApiInclude(cascadeDelete: true)]
    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class); // Uses SoftDeletes - safe to cascade
    }

    #[ApiInclude] // cascadeDelete defaults to false
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Hard-delete example (use with caution!)
    #[ApiInclude(cascadeDelete: true, forceDelete: true)]
    public function metadata(): HasOne
    {
        return $this->hasOne(OrderMetadata::class); // Doesn't use SoftDeletes
    }

    // This relationship will NOT be included in the stream
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
```

**In GET endpoints** (`GET /schema-api/orders`):
The response will stream:
1. The Order entity
2. Each OrderRow entity (as separate root-level entities)
3. The User entity (owner)

All entities are streamed at the root level as separate NDJSON lines, not nested within the parent.

**Cascade Delete Behavior:**
When using the `HasApiIncludeCascadeDelete` trait, models will automatically:
- Delete related items marked with `#[ApiInclude(cascadeDelete: true)]` in the `deleting` event
- Restore related items in the `restoring` event (for soft deletes)
- **Safety Check**: Related models without SoftDeletes will NOT be deleted unless `forceDelete: true`
- **Selective Restore**: Only restores related models deleted at the same time as the parent (within 1 second tolerance)

When deleting an Order in the example above:
- Order rows will be automatically deleted (uses SoftDeletes, cascadeDelete: true)
- Metadata will be hard-deleted (no SoftDeletes, but forceDelete: true)
- Owner will NOT be deleted (cascadeDelete: false)
- The `CollectOperationObserver` tracks all delete operations
- Sync endpoint response includes delete operations for Order, OrderRows, and Metadata

When restoring an Order:
- Only OrderRows deleted at approximately the same time as the Order are restored
- OrderRows deleted before the Order (independently) remain trashed
- This prevents accidentally restoring stale data that was intentionally deleted earlier

**Safety Features**:
1. The `forceDelete` flag protects against accidental hard-deletion of related models that don't use SoftDeletes. Models with SoftDeletes can always be cascade-deleted safely. Models without SoftDeletes require explicit `forceDelete: true` to enable cascade deletion.
2. The selective restore mechanism (1-second tolerance) ensures only related models deleted as part of the cascade are restored, preventing unwanted restoration of independently deleted records.

**Performance Note**: The implementation is highly optimized for large datasets:
- Main query uses `toBase()` to avoid Eloquent model hydration overhead
- Parent items are collected in configurable batches (default: 200)
- For each relationship type, a single `whereIn()` query loads all related records for the entire batch
- Related records also use `toBase()` to avoid hydration
- Only a single temporary model instance is created per relationship type to introspect foreign keys

This means for 1000 Orders with rows and owner relationships:
- 1 query for Orders (streaming with cursor)
- 5 queries total for relationships (1 per batch × 2 relationship types)
- No N+1 problems, minimal memory usage

Configure batch size via `SCHEMA_API_RELATIONSHIP_BATCH_SIZE` env variable or `config/schema-api.php`.

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
- `http.relationship_batch_size` - Number of parent items to batch before loading relationships (default: 200)
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
