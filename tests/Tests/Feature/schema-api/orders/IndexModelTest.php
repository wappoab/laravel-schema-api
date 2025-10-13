<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRowEntry;

it('can list models', function () {
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

    $endpoint = route('schema-api.index', ['table' => 'orders']);
    $response = $this->getJson($endpoint);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/stream+json');

    $items = $response->streamedJson();

    expect($items)->toHaveCount(1);
});
