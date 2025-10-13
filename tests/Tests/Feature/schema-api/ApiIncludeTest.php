<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

beforeEach(function () {
    $this->order = Order::factory()
        ->create([
            'id' => '00000000-0004-0000-0000-000000000001',
            'number' => 1001,
            'text' => 'Test Order',
            'total' => 100.50,
            'owner_id' => User::factory([
                'id' => '00000000-0004-0000-0000-000000000004',
                'name' => 'Test User',
            ]),
        ]);

    $this->orderRow1 = OrderRow::factory()->create([
        'id' => '00000000-0004-0000-0000-000000000002',
        'order_id' => $this->order->id,
        'price' => 50.25,
        'specification' => 'First row',
    ]);

    $this->orderRow2 = OrderRow::factory()->create([
        'id' => '00000000-0004-0000-0000-000000000003',
        'order_id' => $this->order->id,
        'price' => 50.25,
        'specification' => 'Second row',
    ]);
});

it('includes relationships in index when ApiInclude attribute is present on method', function () {
    $endpoint = route('schema-api.index', ['table' => 'orders']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $items = $response->streamedJson();

    expect($items)->toHaveCount(4)
        ->and($items[0])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[0]['id'])->toEqual('00000000-0004-0000-0000-000000000001')
        ->and($items[1])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[1]['id'])->toEqual('00000000-0004-0000-0000-000000000002')
        ->and($items[2])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[2]['id'])->toEqual('00000000-0004-0000-0000-000000000003')
        ->and($items[3])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[3]['id'])->toEqual('00000000-0004-0000-0000-000000000004');
});

it('includes relationships in show when ApiInclude attribute is present on method', function () {
    // Order model has #[ApiInclude] on rows() method
    $endpoint = route('schema-api.show', [
        'table' => 'orders',
        'id' => $this->order->id,
    ]);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $items = $response->streamedJson();

    expect($items)->toHaveCount(4)
        ->and($items[0])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[0]['id'])->toEqual('00000000-0004-0000-0000-000000000001')
        ->and($items[1])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[1]['id'])->toEqual('00000000-0004-0000-0000-000000000002')
        ->and($items[2])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[2]['id'])->toEqual('00000000-0004-0000-0000-000000000003')
        ->and($items[3])->toHaveKeys(['id', 'op', 'type', 'attr'])
        ->and($items[3]['id'])->toEqual('00000000-0004-0000-0000-000000000004');
});
