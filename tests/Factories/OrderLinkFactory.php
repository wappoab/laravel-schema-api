<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderLink;

/**
 * @extends Factory<OrderLink>
 */
class OrderLinkFactory extends Factory
{
    protected $model = OrderLink::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'url' => $this->faker->url(),
        ];
    }
}
