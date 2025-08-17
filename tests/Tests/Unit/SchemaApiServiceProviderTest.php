<?php

use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

it('instance a namespace model provider', function () {
    $postModel = ModelResolver::get('posts');
    expect($postModel)->toBe(Post::class);
});

it('cant instance a namespace model provider with wrong namespace', function () {
    config()->set('schema-api.model_resolver.drivers.namespace.namespace', 'Wappo\\LaravelSchemaApi\\Tests\\Models');
    $postModel = ModelResolver::get('posts');
    expect($postModel)->toBeNull();
});
