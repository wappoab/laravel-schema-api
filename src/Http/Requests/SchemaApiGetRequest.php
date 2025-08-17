<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchemaApiGetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gzip' => ['nullable', 'integer', 'min:0', 'max:9'],
        ];
    }
}