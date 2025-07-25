<?php

namespace Wappo\LaravelSchemaApi\Enums;

enum Operation : string
{
    case create = "I";
    case update = "U";
    case delete = "R";
}
