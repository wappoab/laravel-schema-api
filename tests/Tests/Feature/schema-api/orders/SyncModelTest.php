<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRowEntry;

it('can create order models', function () {
    $uuid = Str::uuid()->toString();
    $rowUuid = Str::uuid()->toString();
    $rowEntryUuid = Str::uuid()->toString();

    $operations = [
        [
            'id' => $uuid,
            'op' => Operation::create->value,
            'type' => 'orders',
            'attr' => [
                'text' => 'A cool order',
                'number' => 1,
            ],
        ],
        [
            'id' => $rowUuid,
            'op' => Operation::create->value,
            'type' => 'order-rows',
            'attr' => [
                'order_id' => $uuid,
                'specification' => 'An order row',
                'price' => 990,
            ],
        ],
        [
            'id' => $rowEntryUuid,
            'op' => Operation::create->value,
            'type' => 'order-row-entries',
            'attr' => [
                'order_row_id' => $rowUuid,
                'specification' => 'A work log',
                'quantity' => 2.5,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3)
        ->and($json[0]['id'])->toBe($uuid)
        ->and($json[0]['op'])->toBe(Operation::create->value)
        ->and($json[0]['type'])->toBe('orders')
        ->and($json[0]['attr']['text'])->toBe('A cool order')
        ->and($json[0]['attr']['number'])->toBe(1)
        ->and($json[0]['attr']['total'])->toBe(2475)
        ->and($json[1]['id'])->toBe($rowUuid)
        ->and($json[1]['op'])->toBe(Operation::create->value)
        ->and($json[1]['type'])->toBe('order-rows')
        ->and($json[1]['attr']['specification'])->toBe('An order row')
        ->and($json[1]['attr']['quantity'])->toBe(2.5)
        ->and($json[1]['attr']['price'])->toBe(990)
        ->and($json[1]['attr']['total'])->toBe(2475)
        ->and($json[2]['id'])->toBe($rowEntryUuid)
        ->and($json[2]['op'])->toBe(Operation::create->value)
        ->and($json[2]['type'])->toBe('order-row-entries')
        ->and($json[2]['attr']['specification'])->toBe('A work log')
        ->and($json[2]['attr']['quantity'])->toBe(2.5)
    ;

    $this->assertDatabaseHas('orders', [
        'id' => $uuid,
        'text' => 'A cool order',
        'number' => 1,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_rows', [
        'id' => $rowUuid,
        'specification' => 'An order row',
        'quantity' => 2.5,
        'price' => 990,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_row_entries', [
        'id' => $rowEntryUuid,
        'specification' => 'A work log',
        'quantity' => 2.5,
    ]);
});

it('update entries updates the order models', function () {

    $orderRowEntry = OrderRowEntry::factory()->create([
        'order_row_id' => OrderRow::factory()->create([
            'order_id' => Order::factory()->create([
                'number' => 1,
                'text' => 'Update order',
            ]),
            'price' => 990,
            'specification' => 'An order row',
        ]),
        'quantity' => 3.5,
        'specification' => 'A work log',
    ]);

    $operations = [
        [
            'id' => $orderRowEntry->id,
            'op' => Operation::update->value,
            'type' => 'order-row-entries',
            'attr' => [
                'quantity' => 2.5,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3)
        ->and($json[0]['id'])->toBe($orderRowEntry->id->toString())
        ->and($json[0]['op'])->toBe(Operation::update->value)
        ->and($json[0]['type'])->toBe('order-row-entries')
        ->and($json[0]['attr']['specification'])->toBe('A work log')
        ->and($json[0]['attr']['quantity'])->toBe(2.5)
        ->and($json[1]['id'])->toBe($orderRowEntry->order_row->id->toString())
        ->and($json[1]['op'])->toBe(Operation::update->value)
        ->and($json[1]['type'])->toBe('order-rows')
        ->and($json[1]['attr']['specification'])->toBe('An order row')
        ->and($json[1]['attr']['quantity'])->toBe(2.5)
        ->and($json[1]['attr']['price'])->toBe(990)
        ->and($json[1]['attr']['total'])->toBe(2475)
        ->and($json[2]['id'])->toBe($orderRowEntry->order_row->order_id->toString())
        ->and($json[2]['op'])->toBe(Operation::update->value)
        ->and($json[2]['type'])->toBe('orders')
        ->and($json[2]['attr']['text'])->toBe('Update order')
        ->and($json[2]['attr']['number'])->toBe(1)
        ->and($json[2]['attr']['total'])->toBe(2475)
    ;

    $this->assertDatabaseHas('orders', [
        'id' => $orderRowEntry->order_row->order_id,
        'text' => 'Update order',
        'number' => 1,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_rows', [
        'id' => $orderRowEntry->order_row->id,
        'specification' => 'An order row',
        'quantity' => 2.5,
        'price' => 990,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_row_entries', [
        'id' => $orderRowEntry->id,
        'specification' => 'A work log',
        'quantity' => 2.5,
    ]);
});

it('deleting entries updates the order models', function () {

    $orderRowEntry = OrderRowEntry::factory()->create([
        'order_row_id' => OrderRow::factory()->create([
            'order_id' => Order::factory()->create([
                'number' => 1,
                'text' => 'Update order',
            ]),
            'price' => 990,
            'specification' => 'An order row',
        ]),
        'quantity' => 3.5,
        'specification' => 'A work log',
    ]);

    $operations = [
        [
            'id' => $orderRowEntry->id,
            'op' => Operation::delete->value,
            'type' => 'order-row-entries',
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3)
        ->and($json[0]['id'])->toBe($orderRowEntry->id->toString())
        ->and($json[0]['op'])->toBe(Operation::delete->value)
        ->and($json[0]['type'])->toBe('order-row-entries')
        ->and($json[0]['attr'])->toBeEmpty()
        ->and($json[1]['id'])->toBe($orderRowEntry->order_row->id->toString())
        ->and($json[1]['op'])->toBe(Operation::update->value)
        ->and($json[1]['type'])->toBe('order-rows')
        ->and($json[1]['attr']['specification'])->toBe('An order row')
        ->and($json[1]['attr']['quantity'])->toBe(0)
        ->and($json[1]['attr']['price'])->toBe(990)
        ->and($json[1]['attr']['total'])->toBe(0)
        ->and($json[2]['id'])->toBe($orderRowEntry->order_row->order_id->toString())
        ->and($json[2]['op'])->toBe(Operation::update->value)
        ->and($json[2]['type'])->toBe('orders')
        ->and($json[2]['attr']['text'])->toBe('Update order')
        ->and($json[2]['attr']['number'])->toBe(1)
        ->and($json[2]['attr']['total'])->toBe(0)
    ;

    $this->assertDatabaseHas('orders', [
        'id' => $orderRowEntry->order_row->order_id,
        'text' => 'Update order',
        'number' => 1,
        'total' => 0,
    ]);
    $this->assertDatabaseHas('order_rows', [
        'id' => $orderRowEntry->order_row->id,
        'specification' => 'An order row',
        'quantity' => 0,
        'price' => 990,
        'total' => 0,
    ]);
    $this->assertDatabaseMissing('order_row_entries', [
        'id' => $orderRowEntry->id,
        'deleted_at' => null,
    ]);
});

it('deleting the order deletes everything', function () {

    $orderRowEntry = OrderRowEntry::factory()->create([
        'order_row_id' => OrderRow::factory()->create([
            'order_id' => Order::factory()->create([
                'number' => 1,
                'text' => 'Update order',
            ]),
            'price' => 990,
            'specification' => 'An order row',
        ]),
        'quantity' => 3.5,
        'specification' => 'A work log',
    ]);

    $operations = [
        [
            'id' => $orderRowEntry->order_row->order_id,
            'op' => Operation::delete->value,
            'type' => 'orders',
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3)
        ->and($json[2]['id'])->toBe($orderRowEntry->id->toString())
        ->and($json[2]['op'])->toBe(Operation::delete->value)
        ->and($json[2]['type'])->toBe('order-row-entries')
        ->and($json[2]['attr'])->toBeEmpty()
        ->and($json[1]['id'])->toBe($orderRowEntry->order_row->id->toString())
        ->and($json[1]['op'])->toBe(Operation::delete->value)
        ->and($json[1]['type'])->toBe('order-rows')
        ->and($json[1]['attr'])->toBeEmpty()
        ->and($json[0]['id'])->toBe($orderRowEntry->order_row->order_id->toString())
        ->and($json[0]['op'])->toBe(Operation::delete->value)
        ->and($json[0]['type'])->toBe('orders')
        ->and($json[0]['attr'])->toBeEmpty()
    ;

    $this->assertDatabaseMissing('orders', [
        'id' => $orderRowEntry->order_row->order_id,
        'deleted_at' => null,
    ]);
    $this->assertDatabaseMissing('order_rows', [
        'id' => $orderRowEntry->order_row->id,
        'deleted_at' => null,

    ]);
    $this->assertDatabaseMissing('order_row_entries', [
        'id' => $orderRowEntry->id,
        'deleted_at' => null,
    ]);
});

it('creating entries updates the order models', function () {

    $orderRow = OrderRow::factory()->create([
        'order_id' => Order::factory()->create([
            'number' => 1,
            'text' => 'Update order',
        ]),
        'price' => 990,
        'specification' => 'An order row',
    ]);

    $rowEntryUuid = Str::uuid()->toString();
    $operations = [
        [
            'id' => $rowEntryUuid,
            'op' => Operation::create->value,
            'type' => 'order-row-entries',
            'attr' => [
                'order_row_id' => $orderRow->id,
                'specification' => 'A work log',
                'quantity' => 2.5,
            ],
        ],
    ];

    $response = $this->putJson(route('schema-api.sync'), $operations);

    $response->assertOk();
    $json = $response->streamedJson();

    expect($json)->toHaveCount(3)
        ->and($json[0]['id'])->toBe($rowEntryUuid)
        ->and($json[0]['op'])->toBe(Operation::create->value)
        ->and($json[0]['type'])->toBe('order-row-entries')
        ->and($json[0]['attr']['specification'])->toBe('A work log')
        ->and($json[0]['attr']['quantity'])->toBe(2.5)
        ->and($json[1]['id'])->toBe($orderRow->id->toString())
        ->and($json[1]['op'])->toBe(Operation::update->value)
        ->and($json[1]['type'])->toBe('order-rows')
        ->and($json[1]['attr']['specification'])->toBe('An order row')
        ->and($json[1]['attr']['quantity'])->toBe(2.5)
        ->and($json[1]['attr']['price'])->toBe(990)
        ->and($json[1]['attr']['total'])->toBe(2475)
        ->and($json[2]['id'])->toBe($orderRow->order_id->toString())
        ->and($json[2]['op'])->toBe(Operation::update->value)
        ->and($json[2]['type'])->toBe('orders')
        ->and($json[2]['attr']['text'])->toBe('Update order')
        ->and($json[2]['attr']['number'])->toBe(1)
        ->and($json[2]['attr']['total'])->toBe(2475)
    ;

    $this->assertDatabaseHas('orders', [
        'id' => $orderRow->order_id,
        'text' => 'Update order',
        'number' => 1,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_rows', [
        'id' => $orderRow->id,
        'specification' => 'An order row',
        'quantity' => 2.5,
        'price' => 990,
        'total' => 2475,
    ]);
    $this->assertDatabaseHas('order_row_entries', [
        'id' => $rowEntryUuid,
        'specification' => 'A work log',
        'quantity' => 2.5,
    ]);
});
