<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Category;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\CategoryPost;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Post;

/**
 * @extends Factory<CategoryPost>
 */
class CategoryPostFactory extends Factory
{
    protected $model = CategoryPost::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
