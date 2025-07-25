<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wappo\LaravelSchemaApi\Attributes\UseValidationRulesProvider;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\Validators\CategoryValidationRulesProvider;

#[UseValidationRulesProvider(CategoryValidationRulesProvider::class)]
class Category extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)
            ->using(CategoryPost::class);
    }
}
