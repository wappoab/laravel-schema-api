<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

it('can list models', function () {
    Post::factory()->count(3)->create();

    $endpoint = route('schema-api.index', ['table' => 'posts']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $items = $response->streamedJson();

    expect($items)->toHaveCount(3);
});

it('can list all data', function () {
    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/index-posts.json');
    foreach ($expectedJson as $postData) {
        Post::factory()->create($postData);
    }

    $endpoint = route('schema-api.index', ['table' => 'posts']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $responseJson = $response->streamedJson();

    expect($responseJson)->toMatchArray($expectedJson);
});
