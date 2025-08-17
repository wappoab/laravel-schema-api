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
            'launch_code' => $this->faker->name(),
            'nuke_payload' => $this->faker->name(),
            'is_armed' => $this->faker->boolean(),
        ];
    }
}
