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

    expect($json)->toHaveCount(3);

    $orderRowEntryJson = collect($json)->first(fn (array $op) => $op['id'] === $rowEntryUuid);
    expect($orderRowEntryJson)->toBeArray()
        ->id->toBe($rowEntryUuid)
        ->op->toBe(Operation::create->value)
        ->type->toBe('order-row-entries')
        ->attr->toBeArray()
        ->attr->specification->toBe('A work log')
        ->attr->quantity->toBe(2.5);

    $orderRowJson = collect($json)->first(fn (array $op) => $op['id'] ===  $rowUuid);
    expect($orderRowJson)->toBeArray()
        ->id->toBe($rowUuid)
        ->op->toBe(Operation::create->value)
        ->type->toBe('order-rows')
        ->attr->toBeArray()
        ->attr->specification->toBe('An order row')
        ->attr->quantity->toBe(2.5)
        ->attr->price->toBe(990)
        ->attr->total->toBe(2475);

    $orderJson = collect($json)->first(fn (array $op) => $op['id'] === $uuid);
    expect($orderJson)->toBeArray()
        ->id->toBe($uuid)
        ->op->toBe(Operation::create->value)
        ->type->toBe('orders')
        ->attr->toBeArray()
        ->attr->text->toBe('A cool order')
        ->attr->number->toBe(1)
        ->attr->total->toBe(2475);

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

    expect($json)->toHaveCount(3);

    $orderRowEntryJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->id->toString());
    expect($orderRowEntryJson)->toBeArray()
        ->id->toBe($orderRowEntry->id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('order-row-entries')
        ->attr->toBeArray()
        ->attr->specification->toBe('A work log')
        ->attr->quantity->toBe(2.5);

    $orderRowJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->id->toString());
    expect($orderRowJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('order-rows')
        ->attr->toBeArray()
        ->attr->specification->toBe('An order row')
        ->attr->quantity->toBe(2.5)
        ->attr->price->toBe(990)
        ->attr->total->toBe(2475);

    $orderJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->order_id->toString());
    expect($orderJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->order_id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('orders')
        ->attr->toBeArray()
        ->attr->text->toBe('Update order')
        ->attr->number->toBe(1)
        ->attr->total->toBe(2475);

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

    expect($json)->toHaveCount(3);

    $orderRowEntryJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->id->toString());
    expect($orderRowEntryJson)->toBeArray()
        ->id->toBe($orderRowEntry->id->toString())
        ->op->toBe(Operation::delete->value)
        ->type->toBe('order-row-entries')
        ->attr->toBeEmpty();

    $orderRowJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->id->toString());
    expect($orderRowJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('order-rows')
        ->attr->toBeArray()
        ->attr->specification->toBe('An order row')
        ->attr->quantity->toBe(0)
        ->attr->price->toBe(990)
        ->attr->total->toBe(0);

    $orderJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->order_id->toString());
    expect($orderJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->order_id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('orders')
        ->attr->toBeArray()
        ->attr->text->toBe('Update order')
        ->attr->number->toBe(1)
        ->attr->total->toBe(0);

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

    expect($json)->toHaveCount(3);

    $orderRowEntryJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->id->toString());
    expect($orderRowEntryJson)->toBeArray()
        ->id->toBe($orderRowEntry->id->toString())
        ->op->toBe(Operation::delete->value)
        ->type->toBe('order-row-entries')
        ->attr->toBeEmpty();

    $orderRowJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->id->toString());
    expect($orderRowJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->id->toString())
        ->op->toBe(Operation::delete->value)
        ->type->toBe('order-rows')
        ->attr->toBeEmpty();

    $orderJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRowEntry->order_row->order_id->toString());
    expect($orderJson)->toBeArray()
        ->id->toBe($orderRowEntry->order_row->order_id->toString())
        ->op->toBe(Operation::delete->value)
        ->type->toBe('orders')
        ->attr->toBeEmpty();

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


    expect($json)->toHaveCount(3);

    $orderRowEntryJson = collect($json)->first(fn (array $op) => $op['id'] === $rowEntryUuid);
    expect($orderRowEntryJson)->toBeArray()
        ->id->toBe($rowEntryUuid)
        ->op->toBe(Operation::create->value)
        ->type->toBe('order-row-entries')
        ->attr->toBeArray()
        ->attr->specification->toBe('A work log')
        ->attr->quantity->toBe(2.5);

    $orderRowJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRow->id->toString());
    expect($orderRowJson)->toBeArray()
        ->id->toBe($orderRow->id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('order-rows')
        ->attr->toBeArray()
        ->attr->specification->toBe('An order row')
        ->attr->quantity->toBe(2.5)
        ->attr->price->toBe(990)
        ->attr->total->toBe(2475);

    $orderJson = collect($json)->first(fn (array $op) => $op['id'] === $orderRow->order_id->toString());
    expect($orderJson)->toBeArray()
        ->id->toBe($orderRow->order_id->toString())
        ->op->toBe(Operation::update->value)
        ->type->toBe('orders')
        ->attr->toBeArray()
        ->attr->text->toBe('Update order')
        ->attr->number->toBe(1)
        ->attr->total->toBe(2475);

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
