<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Gate::before(fn () => true);
});

it('can sync insert models', function () {
    $uuid = Str::uuid()->toString();

    $operations = [
        [
            'operation' => Operation::create->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($uuid)
        ->and($json[0]['name'])->toBe('posts')
        ->and($json[0]['obj']['title'])->toBe('New Post')
        ->and($json[0]['obj']['slug'])->toBe('new-post')
        ->and($json[0]['obj']['status'])->toBe(PostStatus::DRAFT->name)
        ->and($json[0]['obj']['content'])->toBe('Hello world');

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
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $post->id,
                'title' => 'Updated Title',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($post->id)
        ->and($json[0]['obj']['title'])->toBe('Updated Title');

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
            'operation' => Operation::delete->value,
            'name' => 'posts',
            'obj' => [
                'id' => $post->id,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toBeArray()->toHaveCount(1);

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
            'operation' => Operation::create->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'title' => 'Updated Title',
            ],
        ],
        [
            'operation' => Operation::delete->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toBeArray()->toHaveCount(0);

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
            'operation' => Operation::create->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => PostStatus::DRAFT,
                'content' => 'Hello world',            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'title' => 'Updated Title',
            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'slug' => 'updated-title',
            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'status' => PostStatus::PUBLISHED,
            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
                'content' => 'Hello Hi',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($uuid)
        ->and($json[0]['name'])->toBe('posts')
        ->and($json[0]['obj']['title'])->toBe('Updated Title')
        ->and($json[0]['obj']['slug'])->toBe('updated-title')
        ->and($json[0]['obj']['status'])->toBe(PostStatus::PUBLISHED->name)
        ->and($json[0]['obj']['content'])->toBe('Hello Hi');

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
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $post->id,
                'title' => 'Updated Title',
            ],
        ],
        [
            'operation' => Operation::update->value,
            'name' => 'posts',
            'obj' => [
                'id' => $post->id,
                'slug' => 'updated-title',
            ],
        ],
        [
            'operation' => Operation::delete->value,
            'name' => 'posts',
            'obj' => [
                'id' => $post->id,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertOk();
    $json = $response->json();

    expect($json)->toBeArray()->toHaveCount(1);

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
            'operation' => Operation::create->value,
            'name' => 'categories',
            'obj' => [
                'id' => $uuid,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($uuid)
        ->and($json[0]['name'])->toBe('categories')
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
            'operation' => Operation::create->value,
            'name' => 'categories',
            'obj' => [
                'id' => $category->id,
                'name' => '',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($category->id)
        ->and($json[0]['name'])->toBe('categories')
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
            'operation' => Operation::create->value,
            'name' => 'posts',
            'obj' => [
                'id' => $uuid,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), ['operations' => $operations]);

    $response->assertUnprocessable();
    $json = $response->json();

    expect($json)->toHaveCount(1)
        ->and($json[0]['@id'])->toBe($uuid)
        ->and($json[0]['name'])->toBe('posts')
        ->and($json[0]['errors'])->toBeArray()
        ->and($json[0]['errors']['title'][0])->toBe('The title field is required.')
        ->and($json[0]['errors']['slug'][0])->toBe('The slug field is required.')
        ->and($json[0]['errors']['status'][0])->toBe('The status field is required.');

    $this->assertDatabaseMissing('posts', [
        'id' => $uuid,
    ]);
});
