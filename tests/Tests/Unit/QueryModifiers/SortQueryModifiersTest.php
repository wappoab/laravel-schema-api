<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Wappo\LaravelSchemaApi\QueryModifiers\SortQueryModifier;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    Post::factory()->create([
        'title'  => 'Apple',
        'slug'   => 'apple',
        'status' => PostStatus::DRAFT,
    ]);
    Post::factory()->create([
        'title'  => 'Banana',
        'slug'   => 'banana',
        'status' => PostStatus::PUBLISHED,
    ]);
    Post::factory()->create([
        'title'  => 'Cherry',
        'slug'   => 'cherry',
        'status' => PostStatus::ARCHIVED,
    ]);
});

it('sorts posts by the "sort" parameter with asc and desc', function () {
    $req = Request::create('/', 'GET', [
        'sort' => '-slug,title',
    ]);
    app()->instance('request', $req);

    $modifier = new SortQueryModifier(['slug', 'title']);

    $titles = $modifier
        ->modify(Post::query())
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Cherry', 'Banana', 'Apple']);
});

it('ignores sort fields not in the allowed list', function () {
    // Simulate a request with ?sort=-created_at,slug
    $req = Request::create('/', 'GET', [
        'sort' => '-created_at,slug',
    ]);
    app()->instance('request', $req);

    // Only allow sorting on "slug" (created_at is not allowed)
    $modifier = new SortQueryModifier(['slug']);

    $titles = $modifier
        ->modify(Post::query())
        ->pluck('title')
        ->all();

    // Only slug sort applied (apple, banana, cherry)
    expect($titles)
        ->toEqual(['Apple', 'Banana', 'Cherry']);
});
