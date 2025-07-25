<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\ModelResolvers\MorphMapModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    Relation::morphMap([
        'posts' => Post::class,
    ]);

    $this->resolver = new MorphMapModelResolver();
});

it('resolves using morph map', function () {
    expect($this->resolver->get('posts'))->toBe(Post::class);
});

it('returns null for unknown alias', function () {
    expect($this->resolver->get('non_existing'))->toBeNull();
});
