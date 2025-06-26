<?php

// config for Wappo/SchemaApi
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
            'class' => \Wappo\LaravelSchemaApi\Support\NamespaceModelResolver::class,
            'name' => env('SCHEMA_API_MODEL_RESOLVER_NAMESPACE', 'App\\Models'),
        ],
        'morph_map' => [
            'class' => \Wappo\LaravelSchemaApi\Support\MorphMapModelResolver::class,
        ],
        'chained' => [
            'class' => \Wappo\LaravelSchemaApi\Support\NamespaceModelResolver::class,
            'resolvers' => [
                \Wappo\LaravelSchemaApi\Support\NamespaceModelResolver::class,
                \Wappo\LaravelSchemaApi\Support\MorphMapModelResolver::class,
            ],
        ],
    ],
];
