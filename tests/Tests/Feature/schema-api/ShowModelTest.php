<?php

declare(strict_types=1);

use Carbon\Carbon;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\CategoryPost;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Secret;

it('can get models', function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    $post = Post::factory()->create([
        'id' => '00000000-0004-0000-0000-000000000001',
        'title' => 'Post Title',
        'slug' => 'post-title',
        'content' => 'Content content content',
        'status' => PostStatus::PUBLISHED
    ]);

    $endpoint = route('schema-api.show', [
        'table' => 'posts',
        'id' => $post->id,
    ]);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');
    $responseJson = $response->streamedJson();

    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/get-post.json');
    expect($responseJson)->toMatchArray($expectedJson);
});

it('can get models with complex types', function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    $model = CategoryPost::factory()->create([
        'id' => '00000000-0004-0000-0000-000000000001',
    ]);

    $endpoint = route('schema-api.show', [
        'table' => 'category-posts',
        'id' => $model->id,
    ]);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');
    $responseJson = $response->streamedJson();

    expect($responseJson[0])->id->toBe($model->id);
});

it('cant get a hidden model', function () {
    $model = Secret::factory()->create([
        'id' => '00000000-0004-0000-0000-000000000001',
    ]);

    $endpoint = route('schema-api.show', [
        'table' => 'secrets',
        'id' => $model->id,
    ]);
    $response = $this->getJson($endpoint);

    $response->assertNotFound();
});