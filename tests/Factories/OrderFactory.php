<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Order;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'text' => $this->faker->realText(),
            'number' => $this->faker->randomNumber(),
            'total' => 0,
            'owner_id' => User::factory(),
        ];
    }
}
