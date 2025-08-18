<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;

class CategoryPost extends Model
{
    use HasFactory, HasUuids, HasDateFormat;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'category_id',
        'post_id',
    ];
}
