<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;

/**
 * @extends Factory<OrderRow>
 */
class OrderRowFactory extends Factory
{
    protected $model = OrderRow::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'specification' => $this->faker->words(2),
            'price' => $this->faker->randomFloat(2),
        ];
    }
}
