<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Enums;

enum PostStatus implements \JsonSerializable
{
    case DRAFT;
    case PUBLISHED;
    case ARCHIVED;

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
