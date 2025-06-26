<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Wappo\LaravelSchemaApi\Support\NamespaceModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    Config::set('schema-api.resolvers.namespace.name', 'Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models');
    $this->resolver = new NamespaceModelResolver();
});

it('resolves formats table name into model class with namespace', function () {
    expect($this->resolver->resolve('posts'))->toBe(Post::class);
});

it('is invokable', function () {
    expect(($this->resolver)('posts'))->toBe(Post::class);
});

it('returns null for unknown alias', function () {
    expect($this->resolver->resolve('non_existing'))->toBeNull();
});

