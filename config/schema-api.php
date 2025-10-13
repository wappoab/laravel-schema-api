<?php

// config for Wappo/LaravelSchemaApi
return [

    /*
    |--------------------------------------------------------------------------
    | Date format
    |--------------------------------------------------------------------------
    |
    | This value determines the format used by the trait HasDateFormat.
    | Change to Y-m-d\TH:i:s.vP if you need support for microseconds in
    | datetimeTz fields.
    |
    */
    'date_format' => env('SCHEMA_API_DATE_FORMAT', 'Y-m-d\TH:i:sP'),


    'http' => [
        'base_path' => env('SCHEMA_API_HTTP_BASE_PATH', '/schema-api'),
        'middleware' => env('SCHEMA_API_HTTP_MIDDLEWARE', 'api'),
        'gzip_level' => env('SCHEMA_API_GZIP_LEVEL', 0),
        'json_encode_flags' => env('SCHEMA_API_JSON_ENCODE_FLAGS', JSON_UNESCAPED_UNICODE),
        'relationship_batch_size' => env('SCHEMA_API_RELATIONSHIP_BATCH_SIZE', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model resolver
    |--------------------------------------------------------------------------
    |
    | Set resolver class that translate type alias (table name) to a model name.
    | Each resolver have its own set of configuration apart from the actual
    | class that does the resolving magic.
    |
    */
    'model_resolver' => [
        'driver' => 'namespace',
        'drivers' => [
            'namespace' => [
                'class' => \Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver::class,
                'namespace' => env('SCHEMA_API_MODEL_RESOLVER_NAMESPACE', 'App\\Models'),
            ],
            'morph_map' => [
                'class' => \Wappo\LaravelSchemaApi\ModelResolvers\MorphMapModelResolver::class,
            ],
        ],
        'decorators' => [
            \Wappo\LaravelSchemaApi\Support\ValidatingModelResolver::class,
            \Wappo\LaravelSchemaApi\Support\CachingModelResolver::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource resolver
    |--------------------------------------------------------------------------
    |
    | Set resolver class that translate url alias to a model name.
    | Each resolver have its own set of configuration apart from the actual
    | class that does the resolving magic.
    |
    */
    'resource_resolver' => [
        'driver' => 'namespace',
        'drivers' => [
            'namespace' => [
                'class' => \Wappo\LaravelSchemaApi\ResourceResolvers\NamespaceResourceResolver::class,
                'namespace' => env('SCHEMA_API_RESOURCE_RESOLVER_NAMESPACE', 'App\\Resources'),
            ],
            'attribute' => [
                'class' => \Wappo\LaravelSchemaApi\ResourceResolvers\UseSchemaApiJsonResourceAttributeResolver::class,
            ],
            'guesser' => [
                'class' => \Wappo\LaravelSchemaApi\ResourceResolvers\GuessResourceNameResolver::class,
            ],
        ],
        'decorators' => [
            \Wappo\LaravelSchemaApi\Support\ValidatingResourceResolver::class,
            \Wappo\LaravelSchemaApi\Support\CachingResourceResolver::class,
        ],
    ],
];
