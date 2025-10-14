<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wappo\LaravelSchemaApi\Casts\AsUuid;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Observers\CollectOperationObserver;
use Wappo\LaravelSchemaApi\Tests\Factories\OrderLinkFactory;

#[ObservedBy(CollectOperationObserver::class)]
class OrderLink extends Model
{
    use HasFactory, HasUuids, HasDateFormat;

    protected static function newFactory(): OrderLinkFactory
    {
        return OrderLinkFactory::new();
    }

    protected $fillable = [
        'id',
        'url',
        'order_id',
    ];

    protected function casts()
    {
        return [
            'id' => AsUuid::class,
            'order_id' => AsUuid::class,
            'url' => 'string',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
