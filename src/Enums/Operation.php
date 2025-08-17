<?php

namespace Wappo\LaravelSchemaApi\Enums;

enum Operation : string
{
    case create = "C";
    case update = "U";
    case delete = "D";
}
