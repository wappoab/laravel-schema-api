<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Secret;

/**
 * @extends Factory<Secret>
 */
class SecretFactory extends Factory
{
    protected $model = Secret::class;
    public function definition(): array
    {
        return [
            'real_name' => $this->faker->name(),
            'noc_list_name' => $this->faker->name(),
            'is_dead' => $this->faker->boolean(),
        ];
    }
}
