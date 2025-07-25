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
    ],

    /*
    |--------------------------------------------------------------------------
    | Model resolver
    |--------------------------------------------------------------------------
    |
    | Set resolver class that translate url alias to a model name.
    | Each resolver have its own set of configuration apart from the actual
    | class that does the resolving magic.
    |
    */
    'model_resolver' => 'namespace',

    'resolvers' => [
        'namespace' => [
            'class' => \Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver::class,
            'name' => env('SCHEMA_API_MODEL_RESOLVER_NAMESPACE', 'App\\Models'),
        ],
        'morph_map' => [
            'class' => \Wappo\LaravelSchemaApi\ModelResolvers\MorphMapModelResolver::class,
        ],
        'chained' => [
            'class' => \Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver::class,
            'resolvers' => [
                \Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver::class,
                \Wappo\LaravelSchemaApi\ModelResolvers\MorphMapModelResolver::class,
            ],
        ],
    ],
];
