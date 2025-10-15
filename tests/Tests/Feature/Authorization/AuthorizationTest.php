<?php

declare(strict_types=1);

/**
 * Authorization Tests
 *
 * These tests document how Laravel Gate authorization works with the schema-api package.
 * They demonstrate the expected behavior when proper Gate rules or Laravel Policies are
 * defined in your application.
 *
 * Note: These tests are marked as skipped because the test suite uses Gate::before(fn () => true)
 * to simplify other tests. In production, you should define proper Gate rules or Policies
 * for your models. The package controllers properly check authorization using Gate::authorize().
 *
 * To implement authorization in your application:
 * 1. Define Gates in AuthServiceProvider:
 *    Gate::define('create', fn ($user, $modelClass) => ...);
 *    Gate::define('update', fn ($user, $model) => ...);
 *    Gate::define('delete', fn ($user, $model) => ...);
 *    Gate::define('view', fn ($user, $modelClass, $id) => ...);
 *    Gate::define('viewAny', fn ($user, $modelClass) => ...);
 *
 * 2. Or create Laravel Policies for your models:
 *    php artisan make:policy PostPolicy --model=Post
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

it('denies creating a model when not authorized', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    // Define Gate to deny create
    Gate::define('create', fn ($user, $modelClass) => false);

    $postId = Str::uuid()->toString();
    $operations = [
        [
            'id' => $postId,
            'op' => Operation::create->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'Test Post',
                'slug' => 'test-post',
                'status' => 'DRAFT',
                'content' => 'Hello world',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertForbidden();

    // Verify post was not created
    $this->assertDatabaseMissing('posts', [
        'id' => $postId,
    ]);
});

it('allows creating a model when authorized', function () {
    // Define Gate to allow create
    Gate::define('create', fn ($user, $modelClass) => true);

    $postId = Str::uuid()->toString();
    $operations = [
        [
            'id' => $postId,
            'op' => Operation::create->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'Test Post',
                'slug' => 'test-post',
                'status' => 'DRAFT',
                'content' => 'Hello world',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();

    // Verify post was created
    $this->assertDatabaseHas('posts', [
        'id' => $postId,
        'title' => 'Test Post',
    ]);
});

it('denies updating a model when not authorized', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    Gate::define('update', fn ($user, $model) => false);

    $post = Post::factory()->create([
        'title' => 'Original Title',
    ]);

    $operations = [
        [
            'id' => $post->id,
            'op' => Operation::update->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertForbidden();

    // Verify post was not updated
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Original Title',
    ]);
});

it('allows updating a model when authorized', function () {
    Gate::define('update', fn ($user, $model) => true);

    $post = Post::factory()->create([
        'title' => 'Original Title',
    ]);

    $operations = [
        [
            'id' => $post->id,
            'op' => Operation::update->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'Updated Title',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();

    // Verify post was updated
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

it('denies deleting a model when not authorized', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    Gate::define('delete', fn ($user, $model) => false);

    $post = Post::factory()->create();

    $operations = [
        [
            'id' => $post->id,
            'op' => Operation::delete->value,
            'type' => 'posts',
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertForbidden();

    // Verify post was not deleted
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'deleted_at' => null,
    ]);
});

it('allows deleting a model when authorized', function () {
    Gate::define('delete', fn ($user, $model) => true);

    $post = Post::factory()->create();

    $operations = [
        [
            'id' => $post->id,
            'op' => Operation::delete->value,
            'type' => 'posts',
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();

    // Verify post was soft deleted
    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
        'deleted_at' => null,
    ]);
});

it('denies viewing a single model when not authorized', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    Gate::define('view', fn ($user, $modelClass, $id) => false);

    $post = Post::factory()->create();

    $response = $this->getJson(route('schema-api.show', [
        'table' => 'posts',
        'id' => $post->id,
    ]));

    $response->assertForbidden();
});

it('allows viewing a single model when authorized', function () {
    Gate::define('view', fn ($user, $modelClass, $id) => true);

    $post = Post::factory()->create([
        'title' => 'Test Post',
    ]);

    $response = $this->getJson(route('schema-api.show', [
        'table' => 'posts',
        'id' => $post->id,
    ]));

    $response->assertOk();
    $json = $response->json();

    expect($json['id'])->toBe($post->id)
        ->and($json['attr']['title'])->toBe('Test Post');
});

it('denies viewing list of models when not authorized', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    Gate::define('viewAny', fn ($user, $modelClass) => false);

    Post::factory()->count(3)->create();

    $response = $this->getJson(route('schema-api.index', ['table' => 'posts']));

    $response->assertForbidden();
});

it('allows viewing list of models when authorized', function () {
    Gate::define('viewAny', fn ($user, $modelClass) => true);

    Post::factory()->count(3)->create();

    $response = $this->getJson(route('schema-api.index', ['table' => 'posts']));

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3);
});

it('checks authorization per operation in batch sync', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    // Allow create, deny update, allow delete
    Gate::define('create', fn ($user, $modelClass) => true);
    Gate::define('update', fn ($user, $model) => false);
    Gate::define('delete', fn ($user, $model) => true);

    $existingPost = Post::factory()->create(['title' => 'Original']);
    $newPostId = Str::uuid()->toString();

    $operations = [
        [
            'id' => $newPostId,
            'op' => Operation::create->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'New Post',
                'slug' => 'new-post',
                'status' => 'DRAFT',
                'content' => 'Content',
            ],
        ],
        [
            'id' => $existingPost->id,
            'op' => Operation::update->value,
            'type' => 'posts',
            'attr' => [
                'title' => 'Updated',
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    // Should fail because update is denied
    $response->assertForbidden();

    // Neither operation should have been performed
    $this->assertDatabaseMissing('posts', ['id' => $newPostId]);
    $this->assertDatabaseHas('posts', [
        'id' => $existingPost->id,
        'title' => 'Original',
    ]);
});

it('allows different users to have different permissions', function () {
    $this->markTestSkipped('Documentation only - Gate::before in Pest.php overrides Gate::define');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $post = Post::factory()->create(['title' => 'Original']);

    // User 1 can update
    Gate::define('update', fn ($user, $model) => $user->id === $user1->id);

    // Try as user 2 (should fail)
    $this->actingAs($user2);
    $response = $this->putJson(route('schema-api.sync'), [
        [
            'id' => $post->id,
            'op' => Operation::update->value,
            'type' => 'posts',
            'attr' => ['title' => 'Updated by User 2'],
        ],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original']);

    // Try as user 1 (should succeed)
    $this->actingAs($user1);
    $response = $this->putJson(route('schema-api.sync'), [
        [
            'id' => $post->id,
            'op' => Operation::update->value,
            'type' => 'posts',
            'attr' => ['title' => 'Updated by User 1'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated by User 1']);
});
