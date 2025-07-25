<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchemaApiSyncRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'operations' => 'required|array',
            'operations.*.name' =>'required|string',
            'operations.*.operation' =>'required|in:I,U,R',
            'operations.*.obj' =>'required|array',
        ];
    }
}