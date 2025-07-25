<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Wappo\LaravelSchemaApi\QueryModifiers\FilterQueryModifier;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    Post::factory()->create([
        'title' => 'Apple',
        'slug' => 'apple',
        'status' => PostStatus::DRAFT,
        'content' => null,
    ]);
    Post::factory()->create([
        'title' => 'Banana',
        'slug' => 'banana',
        'status' => PostStatus::PUBLISHED,
        'content' => 'banana banana banana'
    ]);
    Post::factory()->create([
        'slug' => 'cherry',
        'title' => 'Cherry',
        'status' => PostStatus::ARCHIVED,
        'content' => 'cherry cherry cherry'
    ]);
});

it('filters posts by the "status" filter parameter', function () {
    $req = Request::create('/', 'GET', [
        'filter' => ['status' => 'PUBLISHED'],
    ]);
    app()->instance('request', $req);

    $modifier = new FilterQueryModifier(['status']);

    $titles = $modifier
        ->modify(Post::query())
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Banana']);
});

it('filters posts by the "content" filter parameter whereNull', function () {
    $req = Request::create('/', 'GET', [
        'filter' => ['content' => ''],
    ]);
    app()->instance('request', $req);

    $modifier = new FilterQueryModifier(['content']);

    $titles = $modifier
        ->modify(Post::query())
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Apple']);
});

it('supports comma-separated lists for whereIn filters', function () {
    $req = Request::create('/', 'GET', [
        'filter' => ['status' => 'DRAFT,ARCHIVED'],
    ]);
    app()->instance('request', $req);

    $modifier = new FilterQueryModifier(['status']);

    $titles = $modifier
        ->modify(Post::query())
        ->orderBy('title') // ensure stable order
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Apple', 'Cherry']);
});

it('can filter when allowAttributes is not provided', function () {
    $req = Request::create('/', 'GET', [
        'filter' => ['status' => 'DRAFT,ARCHIVED'],
    ]);
    app()->instance('request', $req);

    $modifier = new FilterQueryModifier();

    $titles = $modifier
        ->modify(Post::query())
        ->orderBy('title') // ensure stable order
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Apple', 'Cherry']);
});

it('cant filter attributes not provided in allowAttributes', function () {
    $req = Request::create('/', 'GET', [
        'filter' => ['status' => 'DRAFT,ARCHIVED'],
    ]);
    app()->instance('request', $req);

    $modifier = new FilterQueryModifier(['slug']);

    $titles = $modifier
        ->modify(Post::query())
        ->orderBy('title') // ensure stable order
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Apple', 'Banana', 'Cherry']);
});
