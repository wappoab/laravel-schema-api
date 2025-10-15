# Laravel Schema API

[![Run Tests](https://github.com/wappoab/laravel-schema-api/actions/workflows/run-test.yml/badge.svg)](https://github.com/wappoab/laravel-schema-api/actions/workflows/run-test.yml)

Automatically expose your Laravel Eloquent models through a RESTful HTTP API with zero configuration. Perfect for building mobile apps, SPAs, or syncing data between systems.

## What Does This Package Do?

Laravel Schema API automatically creates API endpoints for all your Eloquent models, giving you:

- **Instant REST API**: GET, PUT, DELETE operations on all your models without writing controllers
- **Real-Time Broadcasting**: WebSocket notifications when data changes (via Laravel Echo)
- **Streaming JSON Responses**: Memory-efficient NDJSON streaming for large datasets
- **Incremental Sync**: Built-in `?since` parameter to fetch only changed/deleted records
- **Automatic Relationships**: Stream related models with the `#[ApiInclude]` attribute
- **Cascade Deletes**: Automatically delete/restore related models when parents change
- **Schema-Based Validation**: Auto-generates validation rules from your database schema
- **Type-Safe Client Generation**: Generate TypeScript types and Vue forms from your models

## Installation

Install the package via composer:

```bash
composer require wappo/laravel-schema-api
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="laravel-schema-api-config"
```

That's it! Your models are now accessible via `/schema-api/{table-name}`.

## Quick Start

Once installed, you can immediately access your models:

```bash
# List all posts
GET /schema-api/posts

# Get a specific post
GET /schema-api/posts/123

# Create, update, or delete records
PUT /schema-api/sync
```

### Example: Fetching Data

```bash
curl https://your-app.com/schema-api/posts
```

Response (NDJSON format - one JSON object per line):
```json
{"id":"abc-123","type":"posts","attr":{"title":"Hello World","content":"..."}}
{"id":"def-456","type":"posts","attr":{"title":"Another Post","content":"..."}}
```

### Example: Incremental Sync

Fetch only records modified since a specific timestamp:

```bash
# Get posts updated since 2025-01-01
GET /schema-api/posts?since=2025-01-01T00:00:00Z
```

This returns both updated records and deleted IDs (if using soft deletes).

### Example: Creating/Updating Records

```bash
curl -X PUT https://your-app.com/schema-api/sync \
  -H "Content-Type: application/json" \
  -d '[
    {
      "op": "create",
      "type": "posts",
      "id": "new-uuid",
      "attr": {
        "title": "New Post",
        "content": "Hello!"
      }
    },
    {
      "op": "update",
      "type": "posts",
      "id": "existing-uuid",
      "attr": {
        "title": "Updated Title"
      }
    },
    {
      "op": "delete",
      "type": "posts",
      "id": "old-uuid"
    }
  ]'
```

## Advanced Usage

### Including Relationships

Stream related models alongside parent records using the `#[ApiInclude]` attribute:

```php
use Wappo\LaravelSchemaApi\Attributes\ApiInclude;

class Order extends Model
{
    #[ApiInclude]
    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }

    #[ApiInclude]
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Now when you fetch orders, you'll also get the rows and owner streamed as separate root-level entities:

```bash
GET /schema-api/orders/123
```

Response:
```json
{"id":"123","type":"orders","attr":{"number":1001}}
{"id":"row-1","type":"order_rows","attr":{"specification":"Item 1"}}
{"id":"row-2","type":"order_rows","attr":{"specification":"Item 2"}}
{"id":"user-1","type":"users","attr":{"name":"John Doe"}}
```

### Cascade Delete & Restore

Automatically delete or restore related models when the parent changes:

```php
use Wappo\LaravelSchemaApi\Attributes\ApiInclude;
use Wappo\LaravelSchemaApi\Concerns\HasApiIncludeCascadeDelete;

class Order extends Model
{
    use HasApiIncludeCascadeDelete, SoftDeletes;

    // Related rows will be automatically deleted when order is deleted
    #[ApiInclude(cascadeDelete: true)]
    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }

    // For models without SoftDeletes, you must explicitly allow hard deletion
    #[ApiInclude(cascadeDelete: true, forceDelete: true)]
    public function metadata(): HasOne
    {
        return $this->hasOne(OrderMetadata::class);
    }
}
```

**Safety Features:**
- Models with `SoftDeletes` are always safe to cascade delete
- Models without `SoftDeletes` require `forceDelete: true` to prevent accidental data loss
- When restoring a soft-deleted parent, only related records deleted at approximately the same time are restored (configurable tolerance)

### Validation

Validation rules are automatically generated from your database schema:

```php
// varchar(255) → string|max:255
// integer → integer
// datetime → date
// etc.
```

Or provide custom validation rules:

```php
use Wappo\LaravelSchemaApi\Attributes\UseValidationRulesProvider;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesProviderInterface;

#[UseValidationRulesProvider(PostValidationRules::class)]
class Post extends Model
{
    // ...
}

class PostValidationRules implements ValidationRulesProviderInterface
{
    public function getRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:posts',
            'content' => 'required|string',
        ];
    }
}
```

### Query Modifiers

Customize how models are queried using attributes:

```php
use Wappo\LaravelSchemaApi\Attributes\ApplyQueryModifier;
use Wappo\LaravelSchemaApi\QueryModifiers\LatestFirstModifier;
use Wappo\LaravelSchemaApi\QueryModifiers\FilterQueryModifier;

#[ApplyQueryModifier(LatestFirstModifier::class)]
#[ApplyQueryModifier(FilterQueryModifier::class)]
class Post extends Model
{
    // Posts will be ordered by created_at DESC
    // and support filtering via query parameters
}
```

Built-in modifiers:
- `LatestFirstModifier` - Order by `created_at DESC`
- `UpdatedFirstModifier` - Order by `updated_at DESC`
- `SortQueryModifier` - Custom sorting
- `FilterQueryModifier` - Filter by query parameters

### Exclude Models from API

```php
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;

#[ApiIgnore]
class AdminUser extends Model
{
    // This model will NOT be exposed via the API
}
```

If you want to exclude a model from the HTTP API but still broadcast its changes via WebSockets:

```php
#[ApiIgnore(shouldBroadcast: true)]
class InternalAuditLog extends Model
{
    // Not exposed via HTTP, but changes are broadcast to authorized users
}
```

### Real-Time Broadcasting

Get instant notifications when data changes using Laravel's broadcasting system:

```php
// config/schema-api.php
'broadcasting' => [
    'enabled' => true,
    'mode' => 'model-events', // or 'sync'
],
```

**Two Broadcasting Modes:**

1. **`sync` mode** (default) - Only broadcasts changes from the `/schema-api/sync` endpoint
   - Most predictable and transaction-safe
   - Best for apps that exclusively use the sync endpoint

2. **`model-events` mode** - Broadcasts all Eloquent model changes
   - Captures changes from sync endpoint, console commands, direct Eloquent operations, etc.
   - Respects `#[ApiIgnore]` - won't broadcast excluded models (unless `shouldBroadcast: true`)
   - Best for apps with multiple entry points for data changes

**Client-Side Setup:**

Configure Laravel Echo to listen for real-time updates:

```javascript
// Subscribe to your user's private channel
Echo.private(`user.${userId}`)
  .listen('.model.operation', (operation) => {
    // operation = { id, type, op: 'C'|'U'|'D', attr: {...} }

    if (operation.op === 'C') {
      // Add new record to your UI
    } else if (operation.op === 'U') {
      // Update existing record
    } else if (operation.op === 'D') {
      // Remove deleted record
    }
  });
```

**Authorization:**

By default, the package checks Laravel Gates to determine which users can view a model:

```php
// In your AuthServiceProvider
Gate::define('view', function (User $user, Model $model) {
    // Return true if $user can view $model
    return $user->id === $model->user_id;
});
```

For custom authorization logic, bind your own implementation:

```php
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;

$this->app->singleton(ModelViewAuthorizerInterface::class, function () {
    return new YourCustomAuthorizer();
});
```

**Important:** Make sure you have [Laravel Broadcasting](https://laravel.com/docs/broadcasting) configured with a driver like Pusher, Ably, or Redis.

### Custom JSON Resources

Use your own JSON resource classes:

```php
use Wappo\LaravelSchemaApi\Attributes\UseSchemaApiJsonResource;

#[UseSchemaApiJsonResource(PostResource::class)]
class Post extends Model
{
    // ...
}
```

## API Endpoints

### List Models

```
GET /schema-api/{table}
```

**Query Parameters:**
- `?since=2025-01-01T00:00:00Z` - Incremental sync (returns modified and deleted records)
- `?gzip` - Enable gzip compression

**Response Format:** NDJSON (newline-delimited JSON)

### Get Single Model

```
GET /schema-api/{table}/{id}
```

**Response Format:** NDJSON with the model and any `#[ApiInclude]` relationships

### List All Available Models

```
GET /schema-api
```

Returns metadata about all exposed models.

### Batch Sync

```
PUT /schema-api/sync
```

**Request Body:** Array of operations
```json
[
  {
    "op": "create|update|delete",
    "type": "table-name",
    "id": "record-id",
    "attr": { /* attributes */ }
  }
]
```

**Response:** NDJSON with the result of each operation

## Configuration

Publish and edit `config/schema-api.php`:

```php
return [
    'date_format' => 'Y-m-d\TH:i:s.u\Z', // ISO 8601 format

    'http' => [
        'base_path' => '/schema-api',
        'middleware' => ['api'],
        'gzip_level' => 6, // 0-9, compression level
        'relationship_batch_size' => 200, // Batch size for loading relationships
    ],

    'broadcasting' => [
        'enabled' => false, // Enable WebSocket broadcasting
        'mode' => 'sync', // 'sync' or 'model-events'
    ],

    'restore_soft_delete_tolerance_in_seconds' => 1, // Tolerance for cascade restore

    'model_resolver' => [
        'driver' => 'namespace', // 'namespace' or 'morph-map'
    ],

    'resource_resolver' => [
        'driver' => 'namespace', // How to find JSON resource classes
    ],
];
```

## Performance

The package is optimized for large datasets:

- **Streaming Responses**: Uses cursor pagination and NDJSON to handle millions of records without memory issues
- **Efficient Relationship Loading**: Batches parent records and uses `whereIn()` to avoid N+1 queries
- **Optional Compression**: Enable gzip compression with `?gzip` parameter
- **No Model Hydration**: Uses raw queries (`toBase()`) for better performance

Example: Streaming 1 million records uses constant memory (~50MB) instead of loading everything into RAM.

## Client Code Generation

Generate TypeScript types and Vue forms from your models:

```bash
php artisan app:generate-client-resources
```

This reads your model schemas and generates type-safe client code for your frontend.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Johan Östling](https://github.com/kjostling)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
