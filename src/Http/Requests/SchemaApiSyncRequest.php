<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchemaApiSyncRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gzip' => ['nullable', 'numeric', 'min:0', 'max:9'],
            '*' => 'required|array',
            '*.id' =>'required|string',
            '*.type' =>'required|string',
            '*.op' =>'required|in:C,U,D',
            '*.attr' =>'sometimes|array',
        ];
    }
}