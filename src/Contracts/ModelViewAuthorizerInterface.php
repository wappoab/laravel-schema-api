<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ModelViewAuthorizerInterface
{
    /**
     * Get all user IDs that can view the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Support\Collection<int, int> Collection of user IDs
     */
    public function getUserIdsWhoCanView(Model $model): Collection;
}
