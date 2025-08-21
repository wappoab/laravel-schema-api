<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

it('can sync insert models', function () {
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'id' => $uuid,
            'op' => Operation::create->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($uuid)
        ->and($json[0]['op'])->toBe(Operation::create->value)
        ->and($json[0]['type'])->toBe('posts')
        ->and($json[0]['attr']['title'])->toBe('New Post')
        ->and($json[0]['attr']['slug'])->toBe('new-post')
        ->and($json[0]['attr']['status'])->toBe(PostStatus::DRAFT->name)
        ->and($json[0]['attr']['content'])->toBe('Hello world');

    $this->assertDatabaseHas('posts', [
        'id' => $uuid,
        'title' => 'New Post',
        'slug' => 'new-post',
        'status' => PostStatus::DRAFT,
        'content' => 'Hello world',
    ]);
});

it('can sync update existing models', function () {
    $post = Post::factory()->create([
        'title' => 'Original Title',
        'slug' => 'original',
        'status' => PostStatus::DRAFT,
    ]);

    $operations = [
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $post->id,
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($post->id)
        ->and($json[0]['attr']['title'])->toBe('Updated Title');

    // DB should reflect change
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

it('can sync delete existing models', function () {
    $post = Post::factory()->create();

    $operations = [
        [
            'op' => Operation::delete->value,
            'type' => 'posts',
            'id' => $post->id,
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($post->id)
        ->and($json[0]['op'])->toBe(Operation::delete->value);

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
        'deleted_at' => null,
    ]);
});

it('insert and delete does nothing', function () {
    Event::fake([
        "eloquent.created: " . Post::class,
        "eloquent.updated: " . Post::class,
        "eloquent.deleted: " . Post::class,
    ]);
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'op' => Operation::create->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
        [
            'op' => Operation::delete->value,
            'type' => 'posts',
            'id' => $uuid,
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(0);

    $this->assertDatabaseMissing('posts', [
        'id' => $uuid,
    ]);
    Event::assertNotDispatched("eloquent.created: " . Post::class);
    Event::assertNotDispatched("eloquent.updated: " . Post::class);
    Event::assertNotDispatched("eloquent.deleted: " . Post::class);
});

it('inserts and updates all in one call', function () {
    Event::fake([
        "eloquent.created: " . Post::class,
        "eloquent.updated: " . Post::class,
    ]);
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'op' => Operation::create->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'slug' => 'updated-title',
            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'status' => PostStatus::PUBLISHED,
            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'content' => 'Hello Hi',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($uuid)
        ->and($json[0]['type'])->toBe('posts')
        ->and($json[0]['attr']['title'])->toBe('Updated Title')
        ->and($json[0]['attr']['slug'])->toBe('updated-title')
        ->and($json[0]['attr']['status'])->toBe(PostStatus::PUBLISHED->name)
        ->and($json[0]['attr']['content'])->toBe('Hello Hi');

    $this->assertDatabaseHas('posts', [
        'id' => $uuid,
        'title' => 'Updated Title',
        'slug' => 'updated-title',
        'status' => PostStatus::PUBLISHED,
        'content' => 'Hello Hi',
    ]);

    Event::assertDispatchedTimes("eloquent.created: " . Post::class, 1);
    Event::assertNotDispatched("eloquent.updated: " . Post::class);
});

it('wont update models that are set to be deleted', function () {
    Event::fake([
        "eloquent.deleted: " . Post::class,
        "eloquent.updated: " . Post::class,
    ]);
    $post = Post::factory()->create();

    $operations = [
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $post->id,
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
        [
            'op' => Operation::update->value,
            'type' => 'posts',
            'id' => $post->id,
            'attr' => [
                'slug' => 'updated-title',
            ],
        ],
        [
            'op' => Operation::delete->value,
            'type' => 'posts',
            'id' => $post->id,
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(1);

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
        'deleted_at' => null,
    ]);
    Event::assertDispatchedTimes("eloquent.deleted: " . Post::class, 1);
    Event::assertNotDispatched("eloquent.updated: " . Post::class);
});

it('fails validation on create category from rules provider', function () {
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'op' => Operation::create->value,
            'type' => 'categories',
            'id' => $uuid,
            'attr' => [
                'test' => 'test',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($uuid)
        ->and($json[0]['type'])->toBe('categories')
        ->and($json[0]['errors'])->toBeArray()
        ->and($json[0]['errors']['name'][0])->toBe('The name field is required.');

    $this->assertDatabaseMissing('categories', [
        'id' => $uuid,
    ]);
});

it('fails validation on update from rules provider', function () {
    $category = Category::factory()->create();

    $operations = [
        [
            'op' => Operation::create->value,
            'type' => 'categories',
            'id' => $category->id,
            'attr' => [
                'name' => '',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($category->id)
        ->and($json[0]['type'])->toBe('categories')
        ->and($json[0]['errors'])->toBeArray()
        ->and($json[0]['errors']['name'][0])->toBe('The name field is required.');

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
        'name' => '',
    ]);
});

it('fails validation on create post from rules provider', function () {
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'op' => Operation::create->value,
            'type' => 'posts',
            'id' => $uuid,
            'attr' => [
                'content' => 'A brand new day',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['id'])->toBe($uuid)
        ->and($json[0]['type'])->toBe('posts')
        ->and($json[0]['errors'])->toBeArray()
        ->and($json[0]['errors']['title'][0])->toBe('The title field is required.')
        ->and($json[0]['errors']['slug'][0])->toBe('The slug field is required.')
        ->and($json[0]['errors']['status'][0])->toBe('The status field is required.');

    $this->assertDatabaseMissing('posts', [
        'id' => $uuid,
    ]);
});
