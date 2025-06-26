<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;

class Category extends Model
{
    use HasFactory, HasUuids, HasDateFormat;

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
