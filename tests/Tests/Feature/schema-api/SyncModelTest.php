<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderLink;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

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

it('cascades delete to ApiInclude relationships with cascadeDelete flag', function () {
    // Create an order with rows and owner
    $user = User::factory()->create([
        'id' => '00000000-0005-0000-0000-000000000001',
    ]);

    $order = Order::factory()->create([
        'id' => '00000000-0005-0000-0000-000000000002',
        'number' => 1002,
        'text' => 'Test Order for Delete',
        'owner_id' => $user->id,
    ]);

    $orderRow1 = OrderRow::factory()->create([
        'id' => '00000000-0005-0000-0000-000000000003',
        'order_id' => $order->id,
        'specification' => 'Row 1',
        'quantity' => 1,
        'price' => 10.00,
    ]);

    $orderRow2 = OrderRow::factory()->create([
        'id' => '00000000-0005-0000-0000-000000000004',
        'order_id' => $order->id,
        'specification' => 'Row 2',
        'quantity' => 2,
        'price' => 20.00,
    ]);

    // Delete the order
    $operations = [
        [
            'op' => Operation::delete->value,
            'type' => 'orders',
            'id' => $order->id,
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3);

    $deletedIds = collect($json)->pluck('id')->all();

    expect($deletedIds)->toContain($order->id->toString())
        ->and($deletedIds)->toContain($orderRow1->id->toString())
        ->and($deletedIds)->toContain($orderRow2->id->toString());

    foreach ($json as $item) {
        expect($item['op'])->toBe(Operation::delete->value);
    }

    $this->assertSoftDeleted('orders', ['id' => $order->id]);
    $this->assertSoftDeleted('order_rows', ['id' => $orderRow1->id]);
    $this->assertSoftDeleted('order_rows', ['id' => $orderRow2->id]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

it('force deletes related models without soft deletes when forceDelete is true', function () {
    // Create an order with links (which don't use SoftDeletes)
    $order = Order::factory()->create([
        'id' => '00000000-0006-0000-0000-000000000001',
        'number' => 1003,
        'text' => 'Test Order with Links',
    ]);

    $link1 = OrderLink::factory()->create([
        'id' => '00000000-0006-0000-0000-000000000002',
        'order_id' => $order->id,
        'url' => 'https://example.com/link1',
    ]);

    $link2 = OrderLink::factory()->create([
        'id' => '00000000-0006-0000-0000-000000000003',
        'order_id' => $order->id,
        'url' => 'https://example.com/link2',
    ]);

    // Verify links exist
    $this->assertDatabaseHas('order_links', ['id' => $link1->id]);
    $this->assertDatabaseHas('order_links', ['id' => $link2->id]);

    // Delete the order
    $operations = [
        [
            'op' => Operation::delete->value,
            'type' => 'orders',
            'id' => $order->id,
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    // Should include delete operations for:
    // 1. The order itself
    // 2. The two order links (HasMany with forceDelete: true)
    expect($json)->toHaveCount(3);

    // Collect IDs from the response
    $deletedIds = collect($json)->pluck('id')->all();

    expect($deletedIds)->toContain($order->id->toString())
        ->and($deletedIds)->toContain($link1->id->toString())
        ->and($deletedIds)->toContain($link2->id->toString());

    // All operations should be deletes
    foreach ($json as $item) {
        expect($item['op'])->toBe(Operation::delete->value);
    }

    // Verify the order is soft deleted
    $this->assertSoftDeleted('orders', ['id' => $order->id]);

    // Verify the links are HARD deleted (not soft deleted, completely removed)
    // OrderLink doesn't use SoftDeletes, so forceDelete: true allows hard deletion
    $this->assertDatabaseMissing('order_links', ['id' => $link1->id]);
    $this->assertDatabaseMissing('order_links', ['id' => $link2->id]);
});

it('only restores related models deleted at the same time as parent', closure: function () {
    Carbon::setTestNow('2025-01-01 00:00:00');
    // Create an order with 3 order rows
    $order = Order::factory()->create([
        'id' => '00000000-0007-0000-0000-000000000001',
        'number' => 1004,
        'text' => 'Test Order for Selective Restore',
    ]);

    $row1 = OrderRow::factory()->create([
        'id' => '00000000-0007-0000-0000-000000000002',
        'order_id' => $order->id,
        'specification' => 'Row 1 - deleted earlier',
        'quantity' => 1,
        'price' => 10.00,
    ]);

    $row2 = OrderRow::factory()->create([
        'id' => '00000000-0007-0000-0000-000000000003',
        'order_id' => $order->id,
        'specification' => 'Row 2 - deleted with order',
        'quantity' => 2,
        'price' => 20.00,
    ]);

    $row3 = OrderRow::factory()->create([
        'id' => '00000000-0007-0000-0000-000000000004',
        'order_id' => $order->id,
        'specification' => 'Row 3 - deleted with order',
        'quantity' => 3,
        'price' => 30.00,
    ]);

    Carbon::setTestNow('2025-01-02 00:00:00');
    $row1->delete();
    $this->assertSoftDeleted('order_rows', ['id' => $row1->id]);

    Carbon::setTestNow('2025-01-03 00:00:00');
    $order->delete();

    $this->assertSoftDeleted('orders', ['id' => $order->id]);
    $this->assertSoftDeleted('order_rows', ['id' => $row1->id]);
    $this->assertSoftDeleted('order_rows', ['id' => $row2->id]);
    $this->assertSoftDeleted('order_rows', ['id' => $row3->id]);

    $order->refresh();
    $row1->refresh();
    $row2->refresh();
    $row3->refresh();

    $orderDeletedAt = $order->deleted_at;
    $row1DeletedAt = $row1->deleted_at;
    $row2DeletedAt = $row2->deleted_at;
    $row3DeletedAt = $row3->deleted_at;

    // Verify row1 was deleted much earlier than the order
    expect($row1DeletedAt->diffInSeconds($orderDeletedAt))->toBeGreaterThan(60);

    // Verify row2 and row3 were deleted at approximately the same time as the order
    expect(abs($row2DeletedAt->diffInSeconds($orderDeletedAt)))->toBeLessThanOrEqual(1);
    expect(abs($row3DeletedAt->diffInSeconds($orderDeletedAt)))->toBeLessThanOrEqual(1);

    // Now restore the order
    $order->restore();

    // Verify the order is restored
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);

    // Verify row2 and row3 are restored (they were deleted with the order)
    $this->assertDatabaseHas('order_rows', ['id' => $row2->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('order_rows', ['id' => $row3->id, 'deleted_at' => null]);

    // Verify row1 is STILL deleted (it was deleted before the order)
    $this->assertSoftDeleted('order_rows', ['id' => $row1->id]);
});
