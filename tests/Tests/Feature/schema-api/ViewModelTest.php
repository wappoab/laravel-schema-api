<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Secret;

it('can get models', function () {
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
    $responseJson = $response->json();

    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/get-post.json');
    expect($responseJson)->toMatchArray($expectedJson);
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