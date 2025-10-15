<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Wappo\LaravelSchemaApi\Broadcasting\ModelOperationBroadcast;
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;
use Wappo\LaravelSchemaApi\Listeners\ModelEventBroadcastListener;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Secret;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

beforeEach(function () {
    // Enable broadcasting with model-events mode
    config()->set('schema-api.broadcasting.enabled', true);
    config()->set('schema-api.broadcasting.mode', 'model-events');
});

it('broadcasts when a model is created via Eloquent', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    // Create a post directly via Eloquent (not through sync endpoint)
    $post = Post::factory()->create([
        'title' => 'Test Post',
        'slug' => 'test-post',
        'status' => 'DRAFT',
        'content' => 'Hello world',
    ]);

    // Assert that ModelOperationBroadcast was dispatched
    Event::assertDispatched(ModelOperationBroadcast::class, function ($event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op->value === 'C'
            && $event->operation->type === 'posts';
    });
});

it('broadcasts when a model is updated via Eloquent', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    $post = Post::factory()->create();

    // Clear events from creation
    Event::fake([ModelOperationBroadcast::class]);

    // Update the post
    $post->update(['title' => 'Updated Title']);

    // Assert that ModelOperationBroadcast was dispatched for update
    Event::assertDispatched(ModelOperationBroadcast::class, function ($event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op->value === 'U'
            && $event->operation->type === 'posts';
    });
});

it('broadcasts when a model is deleted via Eloquent', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    $post = Post::factory()->create();

    // Clear events from creation
    Event::fake([ModelOperationBroadcast::class]);

    // Delete the post
    $post->delete();

    // Assert that ModelOperationBroadcast was dispatched for delete
    Event::assertDispatched(ModelOperationBroadcast::class, function ($event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op->value === 'D'
            && $event->operation->type === 'posts';
    });
});

it('broadcasts when a model is restored via Eloquent', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    $post = Post::factory()->create();
    $post->delete();

    // Clear events from creation and deletion
    Event::fake([ModelOperationBroadcast::class]);

    // Restore the post
    $post->restore();

    // Assert that ModelOperationBroadcast was dispatched for restore (as create)
    Event::assertDispatched(ModelOperationBroadcast::class, function ($event) use ($user1, $post) {
        return $event->userId === $user1->id
            && $event->operation->id === $post->id
            && $event->operation->op->value === 'C' // restored is treated as create
            && $event->operation->type === 'posts';
    });
});

it('does not broadcast for models with ApiIgnore attribute by default', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    // Create a User (which has #[ApiIgnore] without shouldBroadcast flag)
    $newUser = User::factory()->create();

    // Assert that ModelOperationBroadcast was NOT dispatched
    // #[ApiIgnore] prevents broadcasting by default
    Event::assertNotDispatched(ModelOperationBroadcast::class);
});

it('broadcasts for models with ApiIgnore(shouldBroadcast: true)', function () {
    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer BEFORE subscribing (listener gets resolved at subscription time)
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Register the event listener manually for tests (after mocking authorizer)
    Event::subscribe(ModelEventBroadcastListener::class);

    // Create a Secret (which has #[ApiIgnore(shouldBroadcast: true)])
    $secret = Secret::factory()->create([
        'launch_code' => 'TOP-SECRET',
        'nuke_payload' => 'CLASSIFIED',
        'is_armed' => true,
    ]);

    // Assert that ModelOperationBroadcast WAS dispatched despite ApiIgnore
    // because shouldBroadcast is true
    Event::assertDispatched(ModelOperationBroadcast::class, function ($event) use ($user1, $secret) {
        return $event->userId === $user1->id
            && $event->operation->id === $secret->id
            && $event->operation->op->value === 'C'
            && $event->operation->type === 'secrets';
    });
});

it('respects mode configuration - sync mode does not broadcast model events', function () {
    config()->set('schema-api.broadcasting.mode', 'sync');

    Event::fake([ModelOperationBroadcast::class]);

    $user1 = User::factory()->create();

    // Mock the authorizer
    $this->app->bind(ModelViewAuthorizerInterface::class, function () use ($user1) {
        return new class($user1) implements ModelViewAuthorizerInterface {
            public function __construct(private $user1) {}

            public function getUserIdsWhoCanView($model): \Illuminate\Support\Collection
            {
                return collect([$this->user1->id]);
            }
        };
    });

    // Create a post
    $post = Post::factory()->create();

    // Assert that ModelOperationBroadcast was NOT dispatched (mode is 'sync', not 'model-events')
    Event::assertNotDispatched(ModelOperationBroadcast::class);
});
