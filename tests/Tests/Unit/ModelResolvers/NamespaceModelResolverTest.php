<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    $namespace = config('schema-api.model_resolver.drivers.namespace.namespace');
    $this->resolver = new NamespaceModelResolver($namespace);
});

it('resolves formats table name into model class with namespace', function () {
    expect($this->resolver->get('posts'))->toBe(Post::class);
});

it('returns null for unknown alias', function () {
    expect($this->resolver->get('non_existing'))->toBeNull();
});

