<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\Support\ChainedModelResolver;
use Wappo\LaravelSchemaApi\Support\MorphMapModelResolver;
use Wappo\LaravelSchemaApi\Support\NamespaceModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;

beforeEach(function () {
    Config::set('schema-api.resolvers.namespace.name', 'Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models');

    Relation::morphMap([
        'posts' => Post::class,
    ]);

    $this->chain = new ChainedModelResolver([
        new MorphMapModelResolver(),
        new NamespaceModelResolver(),
    ]);
});

it('resolves using morph map first', function () {
    expect($this->chain->resolve('posts'))->toBe(Post::class);
});

it('falls back to namespace resolver if morph map misses', function () {
    expect($this->chain->resolve('categories'))->toBe(Category::class);
});

it('returns null if no resolver matches', function () {
    expect($this->chain->resolve('non_existing'))->toBeNull();
});

it('is invokable', function () {
    expect(($this->chain)('posts'))->toBe(Post::class);
});
