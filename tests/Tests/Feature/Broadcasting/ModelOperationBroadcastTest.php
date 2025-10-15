<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Broadcasting\ModelOperationBroadcast;
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

beforeEach(function () {
    // Enable broadcasting for these tests
    config()->set('schema-api.broadcasting.enabled', true);
});

it('broadcasts model operations when enabled', function () {
    Event::fake([ModelOperationBroadcast::class]);

    // Create users
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Mock the authorizer to return specific users
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1, $user2) {
        return new class($user1, $user2) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1, private $user2) {}

            public function getUserIdsWhoCanView($model): Collection
            {
                return collect([$this->user1->id, $this->user2->id]);
            }
        };
    });

    // Create a post via sync endpoint
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

    // Assert that ModelOperationBroadcast was dispatched twice (once for each user)
    Event::assertDispatched(ModelOperationBroadcast::class, 2);

    // Assert broadcasts were sent to correct user channels
    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($user1, $postId) {
        return $event->userId === $user1->id
            && $event->operation->id === $postId
            && $event->operation->op === Operation::create
            && $event->operation->type === 'posts';
    });

    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($user2, $postId) {
        return $event->userId === $user2->id
            && $event->operation->id === $postId
            && $event->operation->op === Operation::create
            && $event->operation->type === 'posts';
    });
});

it('does not broadcast when broadcasting is disabled', function () {
    Event::fake([ModelOperationBroadcast::class]);

    // Disable broadcasting
    config()->set('schema-api.broadcasting.enabled', false);

    // Create a post
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

    // Assert that no broadcasts were dispatched
    Event::assertNotDispatched(ModelOperationBroadcast::class);
});

it('broadcasts update operations', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Create a post first
    $post = Post::factory()->create();

    // Update it
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

    // Assert broadcast for update
    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op === Operation::update
            && $event->operation->type === 'posts';
    });
});

it('broadcasts delete operations', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Create a post
    $post = Post::factory()->create();

    // Delete it
    $operations = [
        [
            'id' => $post->id,
            'op' => Operation::delete->value,
            'type' => 'posts',
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);
    $response->assertOk();

    // Assert broadcast for delete
    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op === Operation::delete
            && $event->operation->type === 'posts';
    });
});

it('broadcasts to multiple users based on gate authorization', function () {
    Event::fake([ModelOperationBroadcast::class]);

    // Create multiple users
    $authorizedUser = User::factory()->create();
    $unauthorizedUser = User::factory()->create();

    // Mock the authorizer to return only the authorized user
    // (Cannot rely on Gate because Pest.php may have Gate::before that allows everything)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($authorizedUser) {
        return new class($authorizedUser) implements ModelViewAuthorizerInterface {
            public function __construct(private $authorizedUser) {}

            public function getUserIdsWhoCanView($model): Collection
            {
                // Simulate Gate authorization: only return the authorized user
                return collect([$this->authorizedUser->id]);
            }
        };
    });

    // Create a post
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

    // Assert exactly 1 broadcast was dispatched (only to authorized user)
    Event::assertDispatchedTimes(ModelOperationBroadcast::class, 1);

    // Assert that broadcast was sent to authorized user
    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($authorizedUser, $postId) {
        return $event->userId === $authorizedUser->id
            && $event->operation->id === $postId
            && $event->operation->op === Operation::create;
    });
});

it('broadcasts the correct data structure', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

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

    // Assert the broadcast has correct structure
    Event::assertDispatched(function (ModelOperationBroadcast $event) use ($postId) {
        $data = $event->broadcastWith();
        return isset($data['id'])
            && isset($data['type'])
            && isset($data['op'])
            && isset($data['attr'])
            && $data['id'] === $postId
            && $data['type'] === 'posts'
            && $data['op'] === Operation::create->value;
    });
});
