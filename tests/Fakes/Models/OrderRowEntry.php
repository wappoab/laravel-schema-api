<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wappo\LaravelSchemaApi\Casts\AsUuid;
use Wappo\LaravelSchemaApi\Concerns\CollectsOperations;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;

class OrderRowEntry extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes, CollectsOperations;

    protected $fillable = [
        'id',
        'order_row_id',
        'specification',
        'quantity',
    ];

    protected function casts()
    {
        return [
            'id' => AsUuid::class,
            'specification' => 'string',
            'quantity' => 'float',
        ];
    }

    public function order_row(): BelongsTo
    {
        return $this->belongsTo(OrderRow::class);
    }

    protected static function booted()
    {
        $recalcTotal = function (self $rowEntry) {
            $rowEntry->order_row?->update(['quantity' => static::query()
                ->where('order_row_id', $rowEntry->order_row_id)
                ->sum('quantity')]);
        };
        static::created($recalcTotal);
        static::updated($recalcTotal);
        static::deleted($recalcTotal);
        static::restored($recalcTotal);
    }
}
