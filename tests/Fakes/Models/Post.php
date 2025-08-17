<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Tests\Fakes\Enums\PostStatus;

class Post extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'status',
        'content',
        'author_id',
    ];

    protected function casts()
    {
        return [
            'id' => 'string',
            'title' => 'string',
            'slug' => 'string',
            'status' => PostStatus::class,
            'content' => 'string',
            'author_id' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->using(CategoryPost::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
