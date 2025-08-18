<?php

declare(strict_types=1);

use Carbon\Carbon;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Secret;

it('can list models', function () {
    Post::factory()->count(3)->create();

    $endpoint = route('schema-api.index', ['table' => 'posts']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $items = $response->streamedJson();

    expect($items)->toHaveCount(3);
});

it('can list all data from one table', closure: function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/index-posts.json');
    foreach ($expectedJson as $postData) {
        Post::factory()->create(['id' => $postData['id'], ...$postData['attr']]);
    }

    $endpoint = route('schema-api.index', ['table' => 'posts']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $responseJson = $response->streamedJson();

    expect($responseJson)->toMatchArray($expectedJson);
});

it('can list all data', closure: function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/index-all.json');
    foreach ($expectedJson as $rowData) {
        $model = ModelResolver::get($rowData['type']);
        if(!$model) {
            continue;
        }
        $model::factory()->create(['id' => $rowData['id'], ...$rowData['attr']]);
    }

    $endpoint = route('schema-api.index');
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $responseJson = $response->streamedJson();

    expect($responseJson->toArray())->toEqualCanonicalizing($expectedJson);
});

it('lists all data except hidden models', closure: function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    $expectedJson = $this->getFixture(__DIR__ . '/Fixtures/index-posts.json');
    foreach ($expectedJson as $postData) {
        Post::factory()->create(['id' => $postData['id'], ...$postData['attr']]);
    }
    Secret::factory()->create();
    $this->assertDatabaseCount('secrets', 1);

    $endpoint = route('schema-api.index');
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $responseJson = $response->streamedJson();

    expect($responseJson)
        ->toHaveCount(count($expectedJson))
        ->toMatchArray($expectedJson);
});