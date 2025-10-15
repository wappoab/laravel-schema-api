<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Attributes;

use Attribute;

#[Attribute]
class ApiIgnore
{
    /**
     * @param bool $shouldBroadcast Whether to broadcast model changes even though model is ignored from API (default: false)
     */
    public function __construct(
        public bool $shouldBroadcast = false
    ) {
    }
}
