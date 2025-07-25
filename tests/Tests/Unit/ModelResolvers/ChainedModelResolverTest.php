<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\ModelResolvers\ChainedModelResolver;
use Wappo\LaravelSchemaApi\ModelResolvers\MorphMapModelResolver;
use Wappo\LaravelSchemaApi\ModelResolvers\NamespaceModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;

beforeEach(function () {
    Relation::morphMap([
        'posts' => Post::class,
    ]);

    $this->chain = new ChainedModelResolver([
        new MorphMapModelResolver(),
        new NamespaceModelResolver(),
    ]);
});

it('resolves using morph map first', function () {
    expect($this->chain->get('posts'))->toBe(Post::class);
});

it('falls back to namespace resolver if morph map misses', function () {
    expect($this->chain->get('categories'))->toBe(Category::class);
});

it('returns null if no resolver matches', function () {
    expect($this->chain->get('non_existing'))->toBeNull();
});