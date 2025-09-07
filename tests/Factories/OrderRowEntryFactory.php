<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRow;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\OrderRowEntry;

/**
 * @extends Factory<OrderRowEntry>
 */
class OrderRowEntryFactory extends Factory
{
    protected $model = OrderRowEntry::class;

    public function definition(): array
    {
        return [
            'order_row_id' => OrderRow::factory(),
            'specification' => $this->faker->words(2),
            'quantity' => $this->faker->randomFloat(2),
        ];
    }
}
