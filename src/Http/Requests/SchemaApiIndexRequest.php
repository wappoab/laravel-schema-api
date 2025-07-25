<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchemaApiIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gzip' => ['nullable', 'numeric', 'min:0', 'max:9'],
        ];
    }
}