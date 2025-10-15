<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Broadcasting;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;

class GateBasedModelViewAuthorizer implements ModelViewAuthorizerInterface
{
    /**
     * Get all user IDs that can view the given model.
     *
     * This implementation uses Laravel's Gate to check the 'view' ability
     * for each user. Override this method to implement custom logic.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Support\Collection<int, int> Collection of user IDs
     */
    public function getUserIdsWhoCanView(Model $model): Collection
    {
        $userModel = $this->getUserModel();

        if (!$userModel) {
            return collect();
        }

        return $userModel::query()
            ->get()
            ->filter(function (Authenticatable $user) use ($model) {
                return Gate::forUser($user)->allows('view', $model);
            })
            ->pluck('id');
    }

    /**
     * Get the user model class.
     *
     * @return class-string<\Illuminate\Contracts\Auth\Authenticatable>|null
     */
    protected function getUserModel(): ?string
    {
        $providers = config('auth.providers', []);
        $defaultProvider = config('auth.guards.web.provider')
            ?? config('auth.defaults.guard');

        if (!isset($providers[$defaultProvider]['model'])) {
            return null;
        }

        return $providers[$defaultProvider]['model'];
    }
}
