<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Wappo\LaravelSchemaApi\QueryModifiers\UpdatedFirstModifier;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

beforeEach(function () {
    Post::factory()->create([
        'title'  => 'Apple',
        'slug'   => 'apple',
        'status' => PostStatus::DRAFT,
    ]);
    Carbon::setTestNow(Carbon::now()->addHour());
    Post::factory()->create([
        'title'  => 'Banana',
        'slug'   => 'banana',
        'status' => PostStatus::PUBLISHED,
    ]);
    Carbon::setTestNow(Carbon::now()->addHour());
    Post::factory()->create([
        'title'  => 'Cherry',
        'slug'   => 'cherry',
        'status' => PostStatus::ARCHIVED,
    ]);
});

it('sorts posts by updated_at', function () {
    $modifier = new UpdatedFirstModifier();

    $titles = $modifier
        ->modify(Post::query())
        ->pluck('title')
        ->all();

    expect($titles)
        ->toEqual(['Cherry', 'Banana', 'Apple']);
});